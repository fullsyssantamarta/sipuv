# Sistema de Gestión de Costos y Precios de Venta

Este documento describe la funcionalidad implementada para el manejo de costos promedio ponderado y gestión de precios de venta en el módulo de compras.

## 🎯 Funcionalidades Implementadas

### 1. **Promedio Ponderado de Compras**
- Cálculo automático del costo promedio ponderado basado en todas las compras del producto
- Conversión automática de monedas utilizando el tipo de cambio
- Información detallada de costos y compras históricas
- Validación visual cuando el precio está por debajo del promedio

### 2. **Edición Directa de Precios de Venta**
- Campo editable para modificar el precio de venta del producto
- Cálculo automático del margen de ganancia en tiempo real
- Actualización automática en la base de datos al perder el foco
- Calculadora de precios sugeridos basada en margen deseado

### 3. **Validaciones Inteligentes**
- Advertencia visual cuando el precio de compra está por debajo del promedio ponderado
- Confirmación requerida para precios por debajo del costo promedio
- Cálculo dinámico de márgenes de ganancia

## 📋 Uso de la Funcionalidad

### En el Formulario de Compras (`item.vue`)

1. **Selección de Producto:**
   - Al seleccionar un producto, se carga automáticamente el promedio ponderado
   - Se muestra el precio de venta actual del producto

2. **Información de Costos:**
   - **Promedio Ponderado:** Costo promedio de todas las compras
   - **Última Compra:** Precio de la compra más reciente
   - **Total Compras:** Número de compras registradas y cantidad total

3. **Edición de Precio de Venta:**
   - Campo editable que muestra el precio de venta actual
   - Calculadora integrada (🧮) para sugerir precios basados en margen
   - Información de margen de ganancia en tiempo real

4. **Validaciones:**
   - ⚠️ Advertencia visual si el precio está por debajo del promedio
   - Confirmación requerida para continuar con precios bajos
   - Actualización automática al cambiar el precio de venta

## 🔧 Implementación Técnica

### Backend (Laravel/PHP)

#### Controlador: `PurchaseController.php`

**Nuevos métodos:**

1. **`getWeightedAverageCost($item_id)`**
```php
GET /purchases/weighted-average-cost/{item_id}
```
- Calcula el promedio ponderado de compras
- Convierte monedas automáticamente
- Retorna información completa de costos

2. **`updateSalePrice(Request $request)`**
```php
POST /purchases/update-sale-price
```
- Actualiza precio de venta del producto
- Soporta dos modos:
  - Actualización directa del precio de venta
  - Cálculo basado en margen de ganancia

**Parámetros de `updateSalePrice`:**
- `item_id`: ID del producto (requerido)
- `sale_unit_price`: Precio de venta directo (opcional)
- `purchase_price`: Precio de compra (opcional, para calcular margen)
- `profit_margin`: Porcentaje de ganancia (opcional, para calcular precio)

### Frontend (Vue.js)

#### Componente: `item.vue`

**Nuevas propiedades de datos:**
```javascript
profit_margin_info: {
    show: false,
    percentage: 0,
    profit: 0
}
```

**Nuevos métodos:**

1. **`loadWeightedAverageInfo()`**
   - Carga información del promedio ponderado vía API
   - Se ejecuta automáticamente al seleccionar un producto

2. **`validateUnitPrice()`**
   - Valida precio contra promedio ponderado
   - Recalcula margen de ganancia

3. **`calculateSuggestedSalePrice()`**
   - Calculadora de precios sugeridos
   - Permite establecer margen de ganancia deseado

4. **`validateSalePrice()`** y **`calculateProfitMargin()`**
   - Validación y cálculo de margen en tiempo real

5. **`updateItemSalePrice()`**
   - Actualiza precio de venta en la base de datos
   - Se ejecuta al perder el foco del campo

### Rutas Web

```php
// Obtener promedio ponderado
Route::get('/purchases/weighted-average-cost/{item_id}', [PurchaseController::class, 'getWeightedAverageCost']);

// Actualizar precio de venta
Route::post('/purchases/update-sale-price', [PurchaseController::class, 'updateSalePrice']);
```

## 🎨 Interfaz de Usuario

### Nuevos Elementos UI

1. **Campo Precio de Venta:**
   - Input editable con símbolo de moneda
   - Botón calculadora (🧮) para sugerir precios
   - Información de margen de ganancia debajo del campo

2. **Panel de Información de Costos:**
   - Diseño compacto con borde y fondo diferenciado
   - Información organizada en líneas separadas
   - Advertencias visuales en color naranja

3. **Botones de Acción:**
   - "Calcular PV": Calculadora de precio de venta
   - Mantiene botones existentes (Cerrar, Agregar/Editar)

### Estilos CSS

```css
.weighted-average-info {
    border: 1px solid #e4e7ed;
    border-radius: 4px;
    padding: 10px;
    background-color: #f9f9f9;
}

.unit-price-warning {
    border-color: #e6a23c !important;
}
```

## 🔄 Flujo de Trabajo

### Escenario 1: Agregar Nuevo Producto a Compra

1. Usuario selecciona producto del dropdown
2. Sistema carga automáticamente:
   - Promedio ponderado de compras anteriores
   - Precio de venta actual del producto
   - Precio de compra sugerido
3. Usuario ingresa cantidad y precio de compra
4. Sistema valida contra promedio ponderado
5. Usuario puede editar precio de venta si es necesario
6. Sistema calcula y muestra margen de ganancia
7. Usuario confirma y agrega el producto

### Escenario 2: Actualizar Precio de Venta

1. Usuario modifica el campo "Precio de Venta"
2. Sistema recalcula margen automáticamente
3. Al perder foco, se actualiza en la base de datos
4. Confirmación de actualización exitosa

### Escenario 3: Calcular Precio Sugerido

1. Usuario hace clic en el botón calculadora (🧮)
2. Sistema solicita porcentaje de ganancia deseado
3. Calcula y sugiere nuevo precio de venta
4. Usuario puede aceptar o modificar la sugerencia

## 🛡️ Validaciones y Seguridad

### Validaciones del Frontend
- Verificación de campos requeridos antes de cálculos
- Confirmación para precios por debajo del promedio
- Validación de formatos numéricos

### Validaciones del Backend
- Existencia del producto en base de datos
- Validación de tipos de datos numéricos
- Manejo de errores con mensajes descriptivos

### Manejo de Errores
- Try-catch en todas las operaciones críticas
- Mensajes de error user-friendly
- Logging de errores para debugging

## 📊 Base de Datos

### Tablas Involucradas

1. **`items`**: Productos principales
   - `sale_unit_price`: Precio de venta unitario
   - `purchase_unit_price`: Precio de compra unitario
   - `percentage_of_profit`: Porcentaje de ganancia

2. **`purchase_items`**: Items de compras
   - `unit_price`: Precio unitario de compra
   - `quantity`: Cantidad comprada
   - `currency_type_id`: Tipo de moneda

3. **`currency_types`**: Tipos de moneda
   - `exchange_rate_sale`: Tipo de cambio

## 🚀 Beneficios del Sistema

### Para el Usuario
- **Información Centralizada**: Todo en una sola vista
- **Decisiones Informadas**: Conoce costos históricos antes de fijar precios
- **Eficiencia**: Actualización rápida de precios de venta
- **Prevención de Pérdidas**: Alertas para precios por debajo del costo

### Para el Negocio
- **Control de Márgenes**: Visibilidad clara de rentabilidad
- **Consistencia**: Precios basados en datos históricos reales
- **Flexibilidad**: Permite ajustes manuales cuando sea necesario
- **Trazabilidad**: Registro de cambios en precios

## 🔮 Posibles Mejoras Futuras

1. **Historial de Cambios**: Registro de modificaciones de precios
2. **Precios por Cliente**: Diferentes precios según tipo de cliente
3. **Alertas Automáticas**: Notificaciones de cambios significativos en costos
4. **Reportes**: Análisis de márgenes por producto/categoría
5. **Precios Dinámicos**: Actualización automática basada en reglas de negocio

---

## 📝 Notas de Implementación

- ✅ Compatible con sistema multi-tenant existente
- ✅ Mantiene compatibilidad hacia atrás
- ✅ Interfaz responsive y accesible
- ✅ Documentación completa del código
- ✅ Manejo robusto de errores

**Versión:** 1.1  
**Fecha:** Enero 2025  
**Estado:** Implementado y Funcional
