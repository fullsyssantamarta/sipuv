#!/bin/bash

# Script para compilar assets del proyecto co-facturadorpro21
# Usa Node.js 14 instalado con NVM

echo "🔧 Cargando Node.js 14 con NVM..."
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"

echo "📦 Versiones:"
echo "Node: $(node --version)"
echo "NPM: $(npm --version)"

echo ""
echo "🏗️  Compilando assets principales..."
cd /root/co-facturadorpro21
npm run production

echo ""
echo "🏗️  Compilando assets del módulo Factcolombia1..."
cd /root/co-facturadorpro21/modules/Factcolombia1
npm run production

echo ""
echo "✅ Compilación completada exitosamente!"
echo "📄 Verifica los cambios en:"
echo "   - /root/co-facturadorpro21/public/js/app.js"
echo "   - /root/co-facturadorpro21/public/css/app.css"
echo "   - /root/co-facturadorpro21/public/js/factcolombia1.js"
