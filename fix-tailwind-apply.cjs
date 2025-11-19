#!/usr/bin/env node

const fs = require('fs');
const path = require('path');
const glob = require('glob');

// Find all Vue files in the resources/js directory
const vueFiles = glob.sync('resources/js/**/*.vue');

console.log(`Found ${vueFiles.length} Vue files`);

let filesModified = 0;

vueFiles.forEach(filePath => {
  let content = fs.readFileSync(filePath, 'utf8');
  let modified = false;
  
  // Check if file has @apply directives
  if (content.includes('@apply')) {
    // Add @reference directive if not present and file has <style scoped>
    if (content.includes('<style scoped>') && !content.includes('@reference')) {
      // Calculate relative path to app.css from this Vue file
      const depth = filePath.split('/').length - 3; // Subtract 'resources/js/' and filename
      const relativePath = '../'.repeat(depth) + '../resources/css/app.css';
      
      content = content.replace(
        /<style scoped>/,
        `<style scoped>\n@reference "${relativePath}";\n`
      );
      modified = true;
    }
    
    // Remove tw- prefix from @apply directives
    // Match @apply followed by any utilities with tw- prefix
    content = content.replace(
      /@apply([^;]+);/g,
      (match, utilities) => {
        // Remove tw- prefix from each utility class
        const cleaned = utilities.replace(/\btw-/g, '');
        return `@apply${cleaned};`;
      }
    );
    modified = true;
  }
  
  if (modified) {
    fs.writeFileSync(filePath, content, 'utf8');
    filesModified++;
    console.log(`✓ Fixed: ${filePath}`);
  }
});

console.log(`\nModified ${filesModified} files`);
