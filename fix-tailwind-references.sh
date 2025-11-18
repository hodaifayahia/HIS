#!/bin/bash

# Find all .vue files that have both <style scoped> and @apply
find resources/js -name "*.vue" -type f | while read -r file; do
    if grep -q "<style scoped>" "$file" && grep -q "@apply" "$file"; then
        # Check if @reference is already present
        if ! grep -q "@reference" "$file"; then
            # Count directory depth from resources/js
            depth=$(echo "$file" | sed 's|resources/js/||' | tr -cd '/' | wc -c)
            
            # Build relative path based on depth
            if [ $depth -eq 0 ]; then
                relpath="../css/app.css"
            elif [ $depth -eq 1 ]; then
                relpath="../../css/app.css"
            elif [ $depth -eq 2 ]; then
                relpath="../../../css/app.css"
            elif [ $depth -eq 3 ]; then
                relpath="../../../../css/app.css"
            else
                relpath="../../../../../css/app.css"
            fi
            
            # Add @reference after <style scoped>
            sed -i "/<style scoped>/a @reference \"$relpath\";" "$file"
            echo "✓ Added @reference to: $file (depth: $depth, path: $relpath)"
        else
            echo "⊙ Already has @reference: $file"
        fi
    fi
done

echo ""
echo "Done! Total files processed."
