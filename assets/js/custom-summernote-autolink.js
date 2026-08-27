/**
 * Convert pasted plain URLs to real <a> links in Summernote immediately
 * (stock AutoLink only runs after Space/Enter).
 */
(function ($) {
    "use strict";

    var URL_RE = /\b((?:https?:\/\/|www\.)[^\s<>"']+[^\s<>"'.,;:!?)\]])/gi;

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function toHref(url) {
        return /^www\./i.test(url) ? ("http://" + url) : url;
    }

    function linkifyPlainText(text) {
        return escapeHtml(text).replace(URL_RE, function (url) {
            var href = toHref(url);
            return '<a href="' + escapeHtml(href) + '" target="_blank" rel="noopener noreferrer">' + url + "</a>";
        });
    }

    function textHasUrl(text) {
        URL_RE.lastIndex = 0;
        return URL_RE.test(String(text || ""));
    }

    function getNativeClipboardEvent(evt) {
        return (evt && evt.originalEvent) ? evt.originalEvent : evt;
    }

    function getClipboardPlain(evt) {
        var native = getNativeClipboardEvent(evt);
        var clip = native && native.clipboardData;
        if (clip) {
            return clip.getData("text/plain") || "";
        }
        if (window.clipboardData) {
            return window.clipboardData.getData("Text") || "";
        }
        return "";
    }

    function getClipboardHtml(evt) {
        var native = getNativeClipboardEvent(evt);
        var clip = native && native.clipboardData;
        return clip ? (clip.getData("text/html") || "") : "";
    }

    function clipboardAlreadyHasAnchor(html) {
        return html && /<a\s[^>]*href=/i.test(html);
    }

    function buildLinkedHtml(text) {
        var trimmed = $.trim(text);
        if (/^(https?:\/\/|www\.)\S+$/i.test(trimmed)) {
            var href = toHref(trimmed);
            return '<a href="' + escapeHtml(href) + '" target="_blank" rel="noopener noreferrer">' + escapeHtml(trimmed) + "</a>";
        }

        return String(text)
            .replace(/\r\n/g, "\n")
            .split("\n")
            .map(function (line) {
                return linkifyPlainText(line) || "<br>";
            })
            .join("<br>");
    }

    function linkifyTextNodes(root) {
        if (!root) {
            return false;
        }

        var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
            acceptNode: function (node) {
                var value = node.nodeValue || "";
                if (!textHasUrl(value)) {
                    return NodeFilter.FILTER_REJECT;
                }
                if ($(node).closest("a, code, pre").length) {
                    return NodeFilter.FILTER_REJECT;
                }
                return NodeFilter.FILTER_ACCEPT;
            }
        });

        var nodes = [];
        while (walker.nextNode()) {
            nodes.push(walker.currentNode);
        }

        if (!nodes.length) {
            return false;
        }

        nodes.forEach(function (textNode) {
            var html = linkifyPlainText(textNode.nodeValue);
            var wrap = document.createElement("span");
            wrap.innerHTML = html;
            var parent = textNode.parentNode;
            if (!parent) {
                return;
            }
            while (wrap.firstChild) {
                parent.insertBefore(wrap.firstChild, textNode);
            }
            parent.removeChild(textNode);
        });

        return true;
    }

    function getEditable($note) {
        return $note.next(".note-editor").find(".note-editable").first();
    }

    function pasteLinkedUrl(nativeEvent, $note) {
        var text = getClipboardPlain(nativeEvent);
        if (!text || !textHasUrl(text)) {
            return false;
        }

        // Keep existing RISE task-comment paste shortcut
        if (text.indexOf("/#comment") > -1) {
            return false;
        }

        // Already a hyperlink from browser/OS
        if (clipboardAlreadyHasAnchor(getClipboardHtml(nativeEvent))) {
            return false;
        }

        nativeEvent.preventDefault();
        $note.summernote("pasteHTML", buildLinkedHtml(text));
        return true;
    }

    $(function () {
        $("body").on("summernote.paste", function (e, nativeEvent) {
            var $note = $(e.target);
            if (!$note.length || typeof $note.summernote !== "function") {
                return;
            }
            pasteLinkedUrl(nativeEvent, $note);
        });

        // Safety net: if URL was typed/pasted as plain text, linkify on blur
        $("body").on("summernote.blur", function (e) {
            var $note = $(e.target);
            var $editable = getEditable($note);
            if (!$editable.length) {
                return;
            }
            if (linkifyTextNodes($editable.get(0))) {
                $note.val($note.summernote("code"));
            }
        });
    });
})(jQuery);
