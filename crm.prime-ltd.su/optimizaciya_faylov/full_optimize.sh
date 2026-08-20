#!/bin/bash
TARGET_DIR="/var/www/crm_prime_lt_usr/data/www/crm.prime-ltd.su/"
LOG_FILE="$PWD/full_optimize.log"

echo "=== СТАРТ ПОЛНОЙ ОПТИМИЗАЦИИ: $(date) ===" | tee -a "$LOG_FILE"
SIZE_BEFORE=$(du -sm "$TARGET_DIR" | cut -f1)
echo "Размер ДО: $SIZE_BEFORE MB" | tee -a "$LOG_FILE"
echo "" | tee -a "$LOG_FILE"

echo "=== 1. JPG / JPEG ===" | tee -a "$LOG_FILE"
find "$TARGET_DIR" -type f \( -iname "*.jpg" -o -iname "*.jpeg" \) | while read file; do
    BEFORE=$(stat -c%s "$file")
    BEFORE_KB=$((BEFORE / 1024))
    echo -n "JPG: $(basename "$file") — " >> "$LOG_FILE"
    OUTPUT=$(jpegoptim --strip-all --all-progressive -m85 "$file" 2>&1)
    AFTER=$(stat -c%s "$file")
    AFTER_KB=$((AFTER / 1024))
    if [ $AFTER -lt $BEFORE ]; then
        SAVED=$((BEFORE - AFTER))
        PERCENT=$((SAVED * 100 / BEFORE))
        echo "${BEFORE_KB}KB → ${AFTER_KB}KB (сжато ${PERCENT}%)" >> "$LOG_FILE"
    else
        echo "${BEFORE_KB}KB → ${AFTER_KB}KB (пропущен)" >> "$LOG_FILE"
    fi
done

echo "=== 2. PNG ===" | tee -a "$LOG_FILE"
find "$TARGET_DIR" -type f -iname "*.png" | while read file; do
    BEFORE=$(stat -c%s "$file")
    BEFORE_KB=$((BEFORE / 1024))
    echo -n "PNG: $(basename "$file") — " >> "$LOG_FILE"
    pngquant --force --quality=65-80 --output "$file.tmp" "$file" 2>/dev/null && mv "$file.tmp" "$file"
    optipng -i0 -o2 "$file" 2>/dev/null
    AFTER=$(stat -c%s "$file")
    AFTER_KB=$((AFTER / 1024))
    if [ $AFTER -lt $BEFORE ]; then
        SAVED=$((BEFORE - AFTER))
        PERCENT=$((SAVED * 100 / BEFORE))
        echo "${BEFORE_KB}KB → ${AFTER_KB}KB (сжато ${PERCENT}%)" >> "$LOG_FILE"
    else
        echo "${BEFORE_KB}KB → ${AFTER_KB}KB (пропущен)" >> "$LOG_FILE"
    fi
done

echo "=== 3. PDF ===" | tee -a "$LOG_FILE"
find "$TARGET_DIR" -type f -iname "*.pdf" | while read file; do
    BEFORE=$(stat -c%s "$file")
    BEFORE_KB=$((BEFORE / 1024))
    echo -n "PDF: $(basename "$file") — " >> "$LOG_FILE"
    gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/printer -dNOPAUSE -dQUIET -dBATCH -sOutputFile="$file.tmp" "$file" 2>/dev/null && mv "$file.tmp" "$file"
    AFTER=$(stat -c%s "$file")
    AFTER_KB=$((AFTER / 1024))
    if [ $AFTER -lt $BEFORE ]; then
        SAVED=$((BEFORE - AFTER))
        PERCENT=$((SAVED * 100 / BEFORE))
        echo "${BEFORE_KB}KB → ${AFTER_KB}KB (сжато ${PERCENT}%)" >> "$LOG_FILE"
    else
        echo "${BEFORE_KB}KB → ${AFTER_KB}KB (пропущен)" >> "$LOG_FILE"
    fi
done

SIZE_AFTER=$(du -sm "$TARGET_DIR" | cut -f1)
SAVED=$((SIZE_BEFORE - SIZE_AFTER))
echo "" | tee -a "$LOG_FILE"
echo "=== ФИНИШ: $(date) ===" | tee -a "$LOG_FILE"
echo "Размер ДО: $SIZE_BEFORE MB" | tee -a "$LOG_FILE"
echo "Размер ПОСЛЕ: $SIZE_AFTER MB" | tee -a "$LOG_FILE"
echo "Сэкономлено: $SAVED MB" | tee -a "$LOG_FILE"