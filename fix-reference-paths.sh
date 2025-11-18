#!/bin/bash

# Fix all @reference paths in Vue files
find resources/js -name "*.vue" -type f | while read -r file; do
    if grep -q "@reference" "$file"; then
        # Count slashes from resources/js/ onwards
        relative_path="${file#resources/js/}"
        depth=$(echo "$relative_path" | tr -cd '/' | wc -c)
        
        # Build correct path: go up (depth + 1) levels, then down to css/app.css
        dots=""
        for i in $(seq 1 $((depth + 1))); do
            dots="${dots}../"
        done
        correct_path="${dots}css/app.css"
        
        # Replace existing @reference line with correct path
        sed -i "s|@reference \".*\";|@reference \"$correct_path\";|g" "$file"
        echo "✓ Fixed: $file -> $correct_path"
    fi
done

echo ""
echo "All paths fixed!"
