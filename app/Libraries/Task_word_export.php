<?php

namespace App\Libraries;

/**
 * Build a minimal .docx from a task title + HTML description (with images).
 */
class Task_word_export {

    public function build($task) {
        $title = trim((string) ($task->title ?? ""));
        // Keep original remote image URLs. localize_remote_content_urls() rewrites
        // them to the current host, which deadlocks PHP's single-threaded server.
        $html = (string) ($task->description ?? "");

        $blocks = $this->parse_blocks($html);
        $media = array();
        $body_xml = $this->blocks_to_document_xml($title, $blocks, $media);

        $tmp = tempnam(sys_get_temp_dir(), "taskdocx");
        if ($tmp === false) {
            throw new \RuntimeException("temp file failed");
        }
        @unlink($tmp);
        $zip_path = $tmp . ".docx";

        $zip = new \ZipArchive();
        if ($zip->open($zip_path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("zip open failed");
        }

        $zip->addFromString("[Content_Types].xml", $this->content_types_xml($media));
        $zip->addFromString("_rels/.rels", $this->rels_xml());
        $zip->addFromString("word/document.xml", $body_xml);
        $zip->addFromString("word/_rels/document.xml.rels", $this->document_rels_xml($media));
        $zip->addFromString("word/styles.xml", $this->styles_xml());

        foreach ($media as $item) {
            $zip->addFromString("word/media/" . $item["name"], $item["bytes"]);
        }

        $zip->close();
        $bytes = file_get_contents($zip_path);
        @unlink($zip_path);

        if ($bytes === false) {
            throw new \RuntimeException("read docx failed");
        }

        return $bytes;
    }

    public function filename_for($task) {
        $id = (int) ($task->id ?? 0);
        $title = trim((string) ($task->title ?? "task"));
        $title = preg_replace('/[^\p{L}\p{N}\-_ ]+/u', "", $title);
        $title = preg_replace('/\s+/', "_", $title);
        $title = trim($title, "_");
        if ($title === "") {
            $title = "task";
        }
        if (function_exists("mb_substr")) {
            $title = mb_substr($title, 0, 60);
        } else {
            $title = substr($title, 0, 60);
        }
        return "task_{$id}_{$title}.docx";
    }

    private function parse_blocks($html) {
        $blocks = array();
        if (trim($html) === "") {
            return $blocks;
        }

        $dom = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $wrapped = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>';
        $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $body = $dom->getElementsByTagName("body")->item(0);
        if (!$body) {
            return $blocks;
        }

        $this->walk_nodes($body, $blocks);
        return $blocks;
    }

    private function walk_nodes(\DOMNode $node, &$blocks) {
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text = trim(preg_replace('/\s+/u', " ", $child->textContent));
                if ($text !== "") {
                    $blocks[] = array("type" => "text", "text" => $text);
                }
                continue;
            }
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $tag = strtolower($child->nodeName);
            if ($tag === "img") {
                $src = $child->attributes && $child->attributes->getNamedItem("src")
                    ? $child->attributes->getNamedItem("src")->nodeValue
                    : "";
                if ($src) {
                    $blocks[] = array("type" => "image", "src" => $src);
                }
                continue;
            }

            if ($tag === "hr") {
                $blocks[] = array("type" => "hr");
                continue;
            }

            if ($tag === "br") {
                $blocks[] = array("type" => "text", "text" => "");
                continue;
            }

            if (in_array($tag, array("p", "div", "li", "h1", "h2", "h3", "h4", "blockquote"), true)) {
                $text = trim(preg_replace('/\s+/u', " ", $this->collect_text_excluding_images($child)));
                if ($text !== "") {
                    $blocks[] = array("type" => "text", "text" => $text);
                }
                // images nested inside the block
                $imgs = $child->getElementsByTagName("img");
                foreach ($imgs as $img) {
                    $src = $img->getAttribute("src");
                    if ($src) {
                        $blocks[] = array("type" => "image", "src" => $src);
                    }
                }
                continue;
            }

            if (in_array($tag, array("ul", "ol", "span", "a", "strong", "b", "em", "i", "table", "tbody", "tr", "td", "th"), true)) {
                $this->walk_nodes($child, $blocks);
            }
        }
    }

    private function collect_text_excluding_images(\DOMNode $node) {
        $text = "";
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text .= $child->textContent;
            } else if ($child->nodeType === XML_ELEMENT_NODE) {
                $tag = strtolower($child->nodeName);
                if ($tag === "img" || $tag === "script" || $tag === "style") {
                    continue;
                }
                if ($tag === "br") {
                    $text .= " ";
                    continue;
                }
                $text .= $this->collect_text_excluding_images($child);
            }
        }
        return $text;
    }

    private function blocks_to_document_xml($title, $blocks, &$media) {
        $paras = "";
        if ($title !== "") {
            $paras .= $this->paragraph_xml($title, true);
        }

        if (!$blocks) {
            $paras .= $this->paragraph_xml("");
        }

        $image_index = 0;
        foreach ($blocks as $block) {
            if ($block["type"] === "text") {
                $paras .= $this->paragraph_xml($block["text"]);
                continue;
            }
            if ($block["type"] === "hr") {
                $paras .= $this->paragraph_xml("——————————————");
                continue;
            }
            if ($block["type"] === "image") {
                $image = $this->load_image($block["src"]);
                if (!$image) {
                    $paras .= $this->paragraph_xml("[" . basename(parse_url($block["src"], PHP_URL_PATH) ?: "image") . "]");
                    continue;
                }
                $image_index++;
                $rid = "rIdImg" . $image_index;
                $ext = $image["ext"];
                $name = "image{$image_index}.{$ext}";
                $media[] = array(
                    "rid" => $rid,
                    "name" => $name,
                    "bytes" => $image["bytes"],
                    "content_type" => $image["content_type"],
                );
                $paras .= $this->image_paragraph_xml($rid, $image["width_emu"], $image["height_emu"]);
            }
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas" '
            . 'xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" '
            . 'xmlns:o="urn:schemas-microsoft-com:office:office" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" '
            . 'xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" '
            . 'xmlns:v="urn:schemas-microsoft-com:vml" '
            . 'xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing" '
            . 'xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" '
            . 'xmlns:w10="urn:schemas-microsoft-com:office:word" '
            . 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
            . 'xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml" '
            . 'xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup" '
            . 'xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk" '
            . 'xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml" '
            . 'xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape" '
            . 'xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" '
            . 'xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture" '
            . 'mc:Ignorable="w14 wp14">'
            . '<w:body>'
            . $paras
            . '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134" w:header="708" w:footer="708" w:gutter="0"/></w:sectPr>'
            . '</w:body></w:document>';
    }

    private function paragraph_xml($text, $bold = false) {
        $text = $this->xml($text);
        $rPr = $bold ? '<w:rPr><w:b/><w:sz w:val="28"/><w:szCs w:val="28"/></w:rPr>' : '<w:rPr><w:sz w:val="22"/><w:szCs w:val="22"/></w:rPr>';
        return '<w:p><w:pPr><w:spacing w:after="120"/></w:pPr><w:r>' . $rPr . '<w:t xml:space="preserve">' . $text . '</w:t></w:r></w:p>';
    }

    private function image_paragraph_xml($rid, $cx, $cy) {
        $docPrId = abs(crc32($rid)) % 100000 + 1;
        return '<w:p><w:r>'
            . '<w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0">'
            . '<wp:extent cx="' . (int) $cx . '" cy="' . (int) $cy . '"/>'
            . '<wp:docPr id="' . $docPrId . '" name="' . $this->xml($rid) . '"/>'
            . '<a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            . '<pic:pic>'
            . '<pic:nvPicPr><pic:cNvPr id="0" name="' . $this->xml($rid) . '"/><pic:cNvPicPr/></pic:nvPicPr>'
            . '<pic:blipFill><a:blip r:embed="' . $this->xml($rid) . '"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
            . '<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . (int) $cx . '" cy="' . (int) $cy . '"/></a:xfrm>'
            . '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>'
            . '</pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing>'
            . '</w:r></w:p>';
    }

    private function load_image($src) {
        $bytes = null;
        $path = $this->resolve_local_path($src);
        if ($path && is_file($path)) {
            $bytes = @file_get_contents($path);
        }
        if ($bytes === null || $bytes === false) {
            foreach ($this->remote_fetch_urls($src) as $url) {
                $bytes = $this->http_get($url);
                if ($bytes !== null && $bytes !== false && $bytes !== "") {
                    break;
                }
            }
        }
        if ($bytes === null || $bytes === false || $bytes === "") {
            return null;
        }

        $info = @getimagesizefromstring($bytes);
        if (!$info) {
            return null;
        }

        $mime = $info["mime"] ?? "image/png";
        $map = array(
            "image/jpeg" => array("jpg", "image/jpeg"),
            "image/jpg" => array("jpg", "image/jpeg"),
            "image/png" => array("png", "image/png"),
            "image/gif" => array("gif", "image/gif"),
            "image/webp" => array("png", "image/png"), // convert not needed if we rewrite? skip webp reencode — store as png only if possible
        );
        if ($mime === "image/webp") {
            if (function_exists("imagecreatefromstring") && function_exists("imagepng")) {
                $im = @imagecreatefromstring($bytes);
                if ($im) {
                    ob_start();
                    imagepng($im);
                    imagedestroy($im);
                    $converted = ob_get_clean();
                    if ($converted) {
                        $bytes = $converted;
                        $mime = "image/png";
                        $info = @getimagesizefromstring($bytes) ?: $info;
                    }
                }
            }
        }
        if (!isset($map[$mime])) {
            $map[$mime] = array("png", "image/png");
        }
        $ext = $map[$mime][0];
        $content_type = $map[$mime][1];

        $px_w = max(1, (int) $info[0]);
        $px_h = max(1, (int) $info[1]);
        // 96 dpi → EMUs; max width ~ 15cm
        $max_w = 5500000;
        $cx = (int) round($px_w * 9525);
        $cy = (int) round($px_h * 9525);
        if ($cx > $max_w) {
            $scale = $max_w / $cx;
            $cx = (int) round($cx * $scale);
            $cy = (int) round($cy * $scale);
        }

        return array(
            "bytes" => $bytes,
            "ext" => $ext,
            "content_type" => $content_type,
            "width_emu" => $cx,
            "height_emu" => $cy,
        );
    }

    private function resolve_local_path($src) {
        $path_part = parse_url($src, PHP_URL_PATH);
        if (!$path_part) {
            $path_part = $src;
        }
        $path_part = urldecode($path_part);
        $file_name = basename($path_part);
        if ($file_name === "" || $file_name === "/" || $file_name === ".") {
            return null;
        }

        $candidates = array();
        $timeline = function_exists("get_setting") ? get_setting("timeline_file_path") : "";
        if ($timeline) {
            $candidates[] = rtrim($timeline, "/\\") . DIRECTORY_SEPARATOR . $file_name;
        }
        $fcpath = defined("FCPATH") ? FCPATH : (getcwd() . DIRECTORY_SEPARATOR);
        $rootpath = defined("ROOTPATH") ? ROOTPATH : $fcpath;
        $candidates[] = $fcpath . "files/timeline_files/" . $file_name;
        $candidates[] = $rootpath . "files/timeline_files/" . $file_name;

        // /files/timeline_files/... relative to FCPATH
        if (strpos($path_part, "/files/") !== false) {
            $rel = substr($path_part, strpos($path_part, "/files/") + 1);
            $candidates[] = $fcpath . $rel;
            $candidates[] = $rootpath . $rel;
        }

        foreach ($candidates as $candidate) {
            if ($candidate && is_file($candidate)) {
                return $candidate;
            }
        }
        return null;
    }

    /**
     * Build candidate remote URLs for an image src, skipping the current host
     * (avoids deadlock on php -S) and preferring files.baseURL / production.
     */
    private function remote_fetch_urls($src) {
        $urls = array();
        $path = parse_url($src, PHP_URL_PATH);
        if (!$path) {
            $path = $src;
        }
        $path = urldecode($path);
        if ($path === "" || $path[0] !== "/") {
            $path = "/" . ltrim($path, "/");
        }

        $bases = array();
        if (function_exists("env")) {
            $files_base = rtrim((string) env("files.baseURL"), "/");
            if ($files_base) {
                $bases[] = $files_base;
            }
        }
        $bases[] = "https://crm.prime-ltd.su";
        $bases[] = "http://crm.prime-ltd.su";

        $current_host = strtolower((string) ($_SERVER["HTTP_HOST"] ?? ""));
        foreach ($bases as $base) {
            $urls[] = $base . $path;
        }

        if (preg_match('#^https?://#i', $src)) {
            $src_host = strtolower((string) parse_url($src, PHP_URL_HOST));
            $is_current = $current_host && ($src_host === $current_host || $src_host === "127.0.0.1" || $src_host === "localhost");
            if (!$is_current) {
                array_unshift($urls, $src);
            }
        }

        return array_values(array_unique(array_filter($urls)));
    }

    private function http_get($url) {
        if (function_exists("curl_init")) {
            $ch = curl_init($url);
            $opts = array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_TIMEOUT => 12,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_USERAGENT => "PrimeCRM-TaskWordExport/1.0",
            );
            if (defined("CURL_IPRESOLVE_V4")) {
                $opts[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
            }
            curl_setopt_array($ch, $opts);
            $data = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code >= 200 && $code < 300 && $data !== false) {
                return $data;
            }
            return null;
        }
        $ctx = stream_context_create(array(
            "http" => array("timeout" => 12, "ignore_errors" => true),
            "ssl" => array("verify_peer" => false, "verify_peer_name" => false),
        ));
        $data = @file_get_contents($url, false, $ctx);
        return $data === false ? null : $data;
    }

    private function content_types_xml($media) {
        $overrides = '';
        foreach ($media as $item) {
            $overrides .= '<Override PartName="/word/media/' . $this->xml($item["name"]) . '" ContentType="' . $this->xml($item["content_type"]) . '"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Default Extension="png" ContentType="image/png"/>'
            . '<Default Extension="jpg" ContentType="image/jpeg"/>'
            . '<Default Extension="jpeg" ContentType="image/jpeg"/>'
            . '<Default Extension="gif" ContentType="image/gif"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            . $overrides
            . '</Types>';
    }

    private function rels_xml() {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '</Relationships>';
    }

    private function document_rels_xml($media) {
        $rels = '<Relationship Id="rIdStyles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        foreach ($media as $item) {
            $rels .= '<Relationship Id="' . $this->xml($item["rid"]) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/' . $this->xml($item["name"]) . '"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $rels
            . '</Relationships>';
    }

    private function styles_xml() {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/><w:qFormat/></w:style>'
            . '</w:styles>';
    }

    private function xml($text) {
        return htmlspecialchars((string) $text, ENT_QUOTES | ENT_XML1, "UTF-8");
    }
}
