const fs = require('fs');
const path = require('path');
const { glob } = require('glob');

async function fixTailwindApply() {
  const files = await glob('resources/js/**/*.vue', { absolute: false });
  
  for (const file of files) {
    let content = fs.readFileSync(file, 'utf-8');
    const originalContent = content;
    
    // Fix @apply statements - add tw- prefix to classes that don't have it
    content = content.replace(/@apply\s+([^;}\n]+)/g, (match, classes) => {
      // Split by spaces and process each class
      const classList = classes.trim().split(/\s+/);
      const fixedClasses = classList.map(cls => {
        // Skip if already has tw- prefix, or if it's a special keyword like !important, ;
        if (cls.startsWith('tw-') || cls.startsWith('!') || cls === '!important') {
          return cls;
        }
        // Add tw- prefix
        return 'tw-' + cls;
      }).join(' ');
      
      return '@apply ' + fixedClasses;
    });
    
    if (content !== originalContent) {
      fs.writeFileSync(file, content, 'utf-8');
      console.log(`✓ Fixed ${file}`);
    }
  }
  
  console.log('All @apply statements have been updated!');
}

fixTailwindApply().catch(console.error);
