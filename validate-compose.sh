#!/bin/sh
# Docker Compose YAML Syntax Validator
# This script validates docker-compose YAML files without requiring Docker

# Check if node is available for YAML parsing
if command -v node &> /dev/null; then
    node -e "
    const fs = require('fs');
    
    // Simple YAML validator (basic checks)
    function validateYaml(file) {
        const content = fs.readFileSync(file, 'utf8');
        
        // Check for unclosed quotes
        const quotes = content.match(/['\"].*?['\"]|['\"].*$/g);
        if (quotes) {
            for (let q of quotes) {
                if (q.split('\"').length - 1 === 1 || q.split(\"'\").length - 1 === 1) {
                    console.error('❌ Unclosed quote in:', file);
                    return false;
                }
            }
        }
        
        // Check for valid indentation (2-space)
        const lines = content.split('\n');
        for (let i = 0; i < lines.length; i++) {
            const line = lines[i];
            if (line.match(/^[^ ]/)) continue; // Skip top-level
            const indent = line.match(/^ */)[0].length;
            if (indent > 0 && indent % 2 !== 0 && line.trim() !== '') {
                console.warn('⚠️  Odd indentation at line', i+1, 'in', file);
            }
        }
        
        // Check for required services
        if (file.includes('docker-compose')) {
            const hasServices = content.includes('services:');
            if (!hasServices) {
                console.error('❌ Missing services: section in', file);
                return false;
            }
        }
        
        console.log('✓ Basic syntax check passed:', file);
        return true;
    }
    
    let valid = true;
    const files = ['docker-compose.yml', 'docker-compose.prod.yml'];
    
    for (let file of files) {
        if (fs.existsSync(file)) {
            if (!validateYaml(file)) {
                valid = false;
            }
        } else {
            console.warn('⚠️  File not found:', file);
        }
    }
    
    process.exit(valid ? 0 : 1);
"
else
    # Fallback: basic grep checks
    echo "Node.js not found, running basic validation..."
    
    for file in docker-compose.yml docker-compose.prod.yml; do
        if [ -f "$file" ]; then
            echo "Checking $file..."
            
            if ! grep -q "^services:" "$file"; then
                echo "❌ Missing services: section in $file"
                exit 1
            fi
            
            if grep -q "ports:" "$file"; then
                echo "✓ Has ports configuration"
            fi
            
            echo "✓ Basic checks passed"
        fi
    done
fi
