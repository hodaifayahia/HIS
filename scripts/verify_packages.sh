#!/usr/bin/env bash
# Quick verification script for package setup

echo "╔═══════════════════════════════════════════════════════════╗"
echo "║   CASCADING AUTO-CONVERSION - DATABASE VERIFICATION       ║"
echo "╚═══════════════════════════════════════════════════════════╝"
echo ""

# Check for CARDIOLOGIE packages
echo "📦 Checking for CARDIOLOGIE packages..."
php -r "
require_once 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\PrestationPackage;

// Find all CARDIOLOGIE packages
\$packages = PrestationPackage::where('name', 'like', '%CARDIO%')
    ->orWhere('name', 'like', '%PACK%')
    ->get(['id', 'name', 'is_active']);

if (\$packages->isEmpty()) {
    echo \"❌ No CARDIOLOGIE packages found\n\n\";
} else {
    echo \"✅ Found \" . \$packages->count() . \" package(s):\n\n\";
    foreach (\$packages as \$pkg) {
        echo \"  Package ID \" . \$pkg->id . \": \" . \$pkg->name . \"\n\";
        echo \"    Active: \" . (\$pkg->is_active ? \"YES ✓\" : \"NO ✗\") . \"\n\";
        
        // Get prestations for this package
        \$prestations = DB::table('prestation_package_items')
            ->where('prestation_package_id', \$pkg->id)
            ->pluck('prestation_id')
            ->toArray();
        
        echo \"    Prestations: [\" . implode(', ', \$prestations) . \"]\n\";
        
        // Show prestation names
        foreach (\$prestations as \$prestId) {
            \$prest = DB::table('prestations')->where('id', \$prestId)->first();
            if (\$prest) {
                echo \"      - \" . \$prestId . \": \" . \$prest->name . \"\n\";
            }
        }
        echo \"\n\";
    }
}
" 2>&1 | grep -v "Warning"

echo ""
echo "═══════════════════════════════════════════════════════════"
echo ""
echo "📋 What we need for cascading to work:"
echo "  ✓ Package with [5, 87] (e.g., PACK CARDIOLOGIE 04)"
echo "  ✓ Package with [5, 87, 88] (e.g., PACK CARDIOLOGIE 05)"
echo ""
