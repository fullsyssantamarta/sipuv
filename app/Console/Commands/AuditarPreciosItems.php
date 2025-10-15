<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant\Item;
use App\Models\Tenant\DocumentItem;

class AuditarPreciosItems extends Command
{
    protected $signature = 'audit:precios-items {--fix : Corregir automáticamente precios faltantes}';
    protected $description = 'Auditar y opcionalmente corregir items sin precio de compra';

    public function handle()
    {
        $this->info('=== AUDITORÍA DE PRECIOS DE ITEMS ===');
        
        // Buscar items sin precio de compra
        $items_sin_precio = Item::where(function($query) {
            $query->where('purchase_unit_price', 0)
                  ->orWhereNull('purchase_unit_price');
        })->get();

        $this->warn("Items sin precio de compra: " . $items_sin_precio->count());
        
        if ($items_sin_precio->count() > 0) {
            $this->table(
                ['Código', 'Nombre', 'Precio Compra', 'Precio Venta'],
                $items_sin_precio->map(function($item) {
                    return [
                        $item->internal_id,
                        substr($item->name, 0, 30) . '...',
                        $item->purchase_unit_price ?? 'NULL',
                        $item->sale_unit_price
                    ];
                })->toArray()
            );
        }

        // Si se especifica --fix, corregir automáticamente
        if ($this->option('fix')) {
            $this->info('Corrigiendo precios automáticamente...');
            
            foreach ($items_sin_precio as $item) {
                if ($item->sale_unit_price > 0) {
                    // Usar 70% del precio de venta como costo estimado
                    $precio_estimado = $item->sale_unit_price * 0.70;
                    $item->purchase_unit_price = $precio_estimado;
                    $item->save();
                    
                    $this->line("✓ {$item->internal_id}: Precio compra actualizado a " . number_format($precio_estimado, 2));
                } else {
                    $this->error("✗ {$item->internal_id}: No se puede estimar precio (sin precio de venta)");
                }
            }
        }

        // Buscar items con utilidad negativa real
        $items_utilidad_negativa = DocumentItem::with('item')
            ->whereHas('item', function($query) {
                $query->whereColumn('purchase_unit_price', '>', 'items.sale_unit_price');
            })
            ->get();

        if ($items_utilidad_negativa->count() > 0) {
            $this->error("ALERTA: Items con utilidad negativa real encontrados:");
            // Mostrar detalles...
        } else {
            $this->info("✓ No se encontraron items con utilidad negativa real");
        }
    }
}