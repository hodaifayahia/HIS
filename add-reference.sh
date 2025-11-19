#!/bin/bash
find resources/js/Components -name "*.vue" -type f | while read file; do
  # Check if file has both "<style scoped>" and "@apply"
  if grep -q "<style scoped>" "$file" && grep -q "@apply" "$file"; then
    # Check if @reference is NOT already present
    if ! grep -q "@reference" "$file"; then
      # Get the depth for relative path (count slashes)
      depth=$(echo "$file" | tr -cd '/' | wc -c)
      # Calculate relative path to resources/css/app.css
      rel_path=$(printf '../%.0s' $(seq 1 $((depth - 2))))
      rel_path="${rel_path}css/app.css"
      
      # Add @reference right after <style scoped>
      sed -i "/<style scoped>/a @reference \"$rel_path\";\n" "$file"
      echo "Added @reference to: $file"
    fi
  fi
done
