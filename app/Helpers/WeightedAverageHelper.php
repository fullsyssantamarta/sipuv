<?php

namespace App\Helpers;

use App\Models\Tenant\PurchaseItem;
use App\Models\Tenant\Item;

class WeightedAverageHelper
{
    /**
     * Calcula el promedio ponderado de compras para un item
     *
     * @param int $item_id
     * @return array
     */
    public static function calculateWeightedAverage($item_id)
    {
        try {
            // Obtener todas las compras del item ordenadas por fecha
            $purchase_items = PurchaseItem::whereHas('purchase', function($query) {
                    $query->whereIn('state_type_id', ['01', '05']); // Registrado o Aceptado (excluye rechazadas '09')
                })
                ->where('item_id', $item_id)
                ->with(['purchase' => function($query) {
                    $query->select('id', 'date_of_issue', 'currency_id');
                }])
                ->orderBy('id', 'desc')
                ->get();

            if ($purchase_items->isEmpty()) {
                return [
                    'success' => true,
                    'weighted_average_cost' => 0,
                    'last_purchase_price' => 0,
                    'total_purchases' => 0,
                    'total_quantity' => 0,
                    'has_purchases' => false,
                    'message' => 'No se encontraron compras para este producto'
                ];
            }

            $total_cost = 0;
            $total_quantity = 0;
            $last_purchase_price = $purchase_items->first()->unit_price ?? 0;
            
            foreach ($purchase_items as $item) {
                $unit_cost = $item->unit_price;
                
                // Sumar al costo total ponderado
                $total_cost += ($unit_cost * $item->quantity);
                $total_quantity += $item->quantity;
            }

            $weighted_average_cost = $total_quantity > 0 ? $total_cost / $total_quantity : 0;

            return [
                'success' => true,
                'weighted_average_cost' => round($weighted_average_cost, 2),
                'last_purchase_price' => round($last_purchase_price, 2),
                'total_purchases' => $purchase_items->count(),
                'total_quantity' => $total_quantity,
                'currency_symbol' => '$',
                'message' => 'Promedio ponderado calculado exitosamente'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'weighted_average_cost' => 0,
                'last_purchase_price' => 0,
                'total_purchases' => 0,
                'total_quantity' => 0,
                'has_purchases' => false,
                'message' => 'Error al calcular el promedio ponderado: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Valida que el precio de venta sea mayor o igual al promedio ponderado
     *
     * @param int $item_id
     * @param float $sale_price
     * @return array
     */
    public static function validateMinimumPrice($item_id, $sale_price)
    {
        $weighted_average = self::calculateWeightedAverage($item_id);
        
        // Si no hay compras, no se valida
        if (!$weighted_average['has_purchases']) {
            return [
                'success' => true,
                'valid' => true,
                'message' => 'No hay historial de compras para validar'
            ];
        }

        $weighted_average_cost = $weighted_average['weighted_average_cost'];
        
        // Validar que el precio de venta sea mayor o igual al promedio ponderado
        if ($sale_price < $weighted_average_cost) {
            return [
                'success' => false,
                'valid' => false,
                'weighted_average_cost' => $weighted_average_cost,
                'sale_price' => $sale_price,
                'difference' => round($weighted_average_cost - $sale_price, 2),
                'message' => "El precio de venta (\${$sale_price}) es menor al costo promedio ponderado (\${$weighted_average_cost}). No se permite generar pérdidas."
            ];
        }

        return [
            'success' => true,
            'valid' => true,
            'weighted_average_cost' => $weighted_average_cost,
            'sale_price' => $sale_price,
            'profit' => round($sale_price - $weighted_average_cost, 2),
            'margin' => $weighted_average_cost > 0 ? round((($sale_price - $weighted_average_cost) / $weighted_average_cost) * 100, 2) : 0,
            'message' => 'Precio válido'
        ];
    }

    /**
     * Valida todos los items de un documento
     *
     * @param array $items
     * @return array
     */
    public static function validateDocumentItems($items)
    {
        $errors = [];
        
        foreach ($items as $index => $item) {
            $item_id = $item['item_id'] ?? null;
            $unit_price = $item['unit_price'] ?? 0;
            
            if (!$item_id) {
                continue;
            }

            // Obtener el item para verificar el precio
            $itemModel = Item::find($item_id);
            if (!$itemModel) {
                continue;
            }

            $validation = self::validateMinimumPrice($item_id, $unit_price);
            
            if (!$validation['valid']) {
                $errors[] = [
                    'item_index' => $index,
                    'item_id' => $item_id,
                    'item_name' => $itemModel->name ?? $itemModel->description,
                    'internal_id' => $itemModel->internal_id,
                    'weighted_average_cost' => $validation['weighted_average_cost'],
                    'sale_price' => $validation['sale_price'],
                    'difference' => $validation['difference'],
                    'message' => $validation['message']
                ];
            }
        }

        if (!empty($errors)) {
            $error_messages = [];
            foreach ($errors as $error) {
                $error_messages[] = "• {$error['item_name']} ({$error['internal_id']}): Precio \${$error['sale_price']} - Costo promedio \${$error['weighted_average_cost']} - Diferencia: \${$error['difference']}";
            }
            
            return [
                'success' => false,
                'valid' => false,
                'errors' => $errors,
                'message' => "Los siguientes productos tienen precio de venta por debajo del costo promedio:\n\n" . implode("\n", $error_messages)
            ];
        }

        return [
            'success' => true,
            'valid' => true,
            'message' => 'Todos los precios son válidos'
        ];
    }
}
