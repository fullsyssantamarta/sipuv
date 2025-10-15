<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant\DocumentItem;

class DebugUtilidad extends Command
{
    protected $signature = 'debug:utilidad';
    protected $description = 'Debug utilidades negativas';

    public function handle()
    {
        $this->info('=== ANÁLISIS DE UTILIDADES EN REPORTE ===');
        
        $items = DocumentItem::with(['item', 'document'])
            ->orderBy('id', 'desc')
            ->take(10)
            ->get();

        foreach($items as $item) {
            $data = $item->getDataReportSoldItems();
            
            $this->line("Item: " . $data['name']);
            $this->line("  - Código: " . $data['internal_id']);
            $this->line("  - Cantidad: " . $data['quantity']);
            $this->line("  - Precio Venta Unitario: " . $item->unit_price);
            $this->line("  - Precio Compra Unitario: " . ($item->relation_item ? $item->relation_item->purchase_unit_price : 'N/A'));
            $this->line("  - Costo Total: " . $data['cost']);
            $this->line("  - Venta Total: " . $data['net_value']);
            $this->line("  - Utilidad: " . $data['utility']);
            $this->line("  - Document ID: " . $item->document_id);
            $this->line("--------------------------------");
        }
    }
}