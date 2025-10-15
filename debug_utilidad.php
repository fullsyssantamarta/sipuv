<?php

require __DIR__ . '/bootstrap/app.php';

use App\Models\Tenant\DocumentItem;

// Simular ambiente de tenant
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Configurar tenant (usando tenancy_principal)
putenv('DB_DATABASE=tenancy_principal');
config(['database.connections.tenant.database' => 'tenancy_principal']);

$items = DocumentItem::with(['item', 'document'])
    ->orderBy('id', 'desc')
    ->take(10)
    ->get();

echo "=== ANÁLISIS DE UTILIDADES EN REPORTE ===\n\n";

foreach($items as $item) {
    $data = $item->getDataReportSoldItems();
    
    echo "Item: " . $data['name'] . "\n";
    echo "  - Código: " . $data['internal_id'] . "\n";
    echo "  - Cantidad: " . $data['quantity'] . "\n";
    echo "  - Precio Venta Unitario: " . $item->unit_price . "\n";
    echo "  - Precio Compra Unitario: " . ($item->relation_item ? $item->relation_item->purchase_unit_price : 'N/A') . "\n";
    echo "  - Costo Total: " . $data['cost'] . "\n";
    echo "  - Venta Total: " . $data['net_value'] . "\n";
    echo "  - Utilidad: " . $data['utility'] . "\n";
    echo "  - Document ID: " . $item->document_id . "\n";
    echo "--------------------------------\n";
}