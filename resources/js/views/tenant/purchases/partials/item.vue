<template>
    <el-dialog :title="titleDialog" :visible="showDialog" @open="create" @close="close">
        <form autocomplete="off" @submit.prevent="clickAddItem">
            <div class="form-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group" :class="{'has-danger': errors.item_id}">
                            <label class="control-label">
                                Producto/Servicio
                                <a href="#" @click.prevent="showDialogNewItem = true">[+ Nuevo]</a>
                            </label>
                            <el-select v-model="form.item_id" @change="changeItem" filterable>
                                <el-option v-for="option in items" :key="option.id" :value="option.id" :label="option.full_description"></el-option>
                            </el-select>
                            <small class="form-control-feedback" v-if="errors.item_id" v-text="errors.item_id[0]"></small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group" :class="{'has-danger': errors.tax_id}">
                            <label class="control-label">Impuesto</label>
                            <el-select v-model="form.tax_id"  filterable>
                                <el-option v-for="option in itemTaxes" :key="option.id" :value="option.id" :label="option.name"></el-option>
                            </el-select>
                            <!-- <el-checkbox :disabled="recordItem != null" v-model="change_tax_id">Editar</el-checkbox> -->
                            <small class="form-control-feedback" v-if="errors.tax_id" v-text="errors.tax_id[0]"></small>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group" :class="{'has-danger': errors.quantity}">
                            <label class="control-label">Cantidad</label>
                            <el-input-number v-model="form.quantity" :min="0.01"></el-input-number>
                            <small class="form-control-feedback" v-if="errors.quantity" v-text="errors.quantity[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group" :class="{'has-danger': errors.unit_price}">
                            <label class="control-label">Precio Unitario</label>
                            <el-input v-model="form.unit_price" 
                                     @input="validateUnitPrice"
                                     :class="{'unit-price-warning': unit_price_warning}">
                                <template slot="prepend" v-if="form.item.currency_type_symbol">{{ form.item.currency_type_symbol }}</template>
                            </el-input>
                            <small class="form-control-feedback" v-if="errors.unit_price" v-text="errors.unit_price[0]"></small>
                        </div>
                    </div>
                    
                    <!-- Precio de Venta -->
                    <div class="col-md-3" v-if="form.item_id">
                        <div class="form-group" :class="{'has-danger': errors.sale_unit_price}">
                            <label class="control-label">
                                Precio de Venta
                                <el-button v-if="form.unit_price > 0" type="text" size="mini" @click="calculateSuggestedSalePrice">
                                    <i class="fa fa-calculator"></i>
                                </el-button>
                            </label>
                            <el-input v-model="form.sale_unit_price" @input="validateSalePrice" @blur="updateItemSalePrice">
                                <template slot="prepend" v-if="form.item.currency_type_symbol">{{ form.item.currency_type_symbol }}</template>
                            </el-input>
                            <small class="form-control-feedback" v-if="errors.sale_unit_price" v-text="errors.sale_unit_price[0]"></small>
                            <small class="text-muted" v-if="profit_margin_info.show">
                                Margen: {{ profit_margin_info.percentage }}% | Ganancia: {{ form.item.currency_type_symbol }}{{ profit_margin_info.profit }}
                            </small>
                        </div>
                    </div>
                    
                    <!-- Información del promedio ponderado -->
                    <div class="col-md-6" v-if="weighted_average_info.show">
                        <div class="form-group">
                            <label class="control-label">Información de Costos</label>
                            <div class="weighted-average-info">
                                <small class="text-muted d-block">
                                    <strong>Promedio Ponderado:</strong> {{ weighted_average_info.currency_symbol }}{{ weighted_average_info.weighted_average_cost }}
                                </small>
                                <small class="text-muted d-block">
                                    <strong>Última Compra:</strong> {{ weighted_average_info.currency_symbol }}{{ weighted_average_info.last_purchase_price }}
                                </small>
                                <small class="text-muted d-block">
                                    <strong>Total Compras:</strong> {{ weighted_average_info.total_purchases }} 
                                    ({{ weighted_average_info.total_quantity }} unidades)
                                </small>
                                <small class="text-warning d-block" v-if="unit_price_warning">
                                    <i class="fa fa-warning"></i> Precio por debajo del promedio ponderado
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group" :class="{'has-danger': errors.warehouse_id}">
                            <label class="control-label">Almacén de destino</label>
                            <el-select v-model="form.warehouse_id"   filterable  >
                                <el-option v-for="option in warehouses" :key="option.id" :value="option.id" :label="option.description"></el-option>
                            </el-select>
                            <small class="form-control-feedback" v-if="errors.warehouse_id" v-text="errors.warehouse_id[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-6 mt-2" v-if="form.item_id">
                        <div class="form-group" :class="{'has-danger': errors.lot_code}" v-if="form.item.lots_enabled">
                            <label class="control-label">
                                Código lote
                            </label>
                            <el-input v-model="lot_code" >
                                <!--<el-button slot="append" icon="el-icon-edit-outline"  @click.prevent="clickLotcode"></el-button> -->
                            </el-input>
                            <small class="form-control-feedback" v-if="errors.lot_code" v-text="errors.lot_code[0]"></small>
                        </div>
                    </div>
                    <div style="padding-top: 1%;" class="col-md-3" v-show="form.item_id">
                        <div class="form-group" :class="{'has-danger': errors.date_of_due}" v-if="form.item.lots_enabled">
                            <label class="control-label">Fec. Vencimiento</label>
                            <el-date-picker v-model="form.date_of_due" type="date" value-format="yyyy-MM-dd" :clearable="true"></el-date-picker>
                            <small class="form-control-feedback" v-if="errors.date_of_due" v-text="errors.date_of_due[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-3" v-show="form.item_id">  <br>
                        <div class="form-group" :class="{'has-danger': errors.lot_code}" v-if="form.item.series_enabled">
                            <label class="control-label">
                                <!-- <el-checkbox v-model="enabled_lots"  @change="changeEnabledPercentageOfProfit">Código lote</el-checkbox> -->
                                Ingrese series
                            </label>

                            <el-button style="margin-top:2%;" type="primary" icon="el-icon-edit-outline"  @click.prevent="clickLotcode"></el-button>

                            <small class="form-control-feedback" v-if="errors.lot_code" v-text="errors.lot_code[0]"></small>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <div class="form-group"  :class="{'has-danger': errors.discount}">
                            <label class="control-label">Descuento</label>
                            <el-input v-model="form.discount"
                                min="0"
                                class="input-with-select"
                                :disabled="!form.item_id">
                                <el-select v-model="form.discount_type"
                                    slot="prepend"
                                    :disabled="!form.item_id">
                                    <el-option label="%" value="percentage"></el-option>
                                    <el-option :label="form.item.currency_type_symbol" value="amount"></el-option>
                                </el-select>
                            </el-input>
                            <small class="form-control-feedback" v-if="errors.discount" v-text="errors.discount[0]"></small>
                        </div>
                    </div>

                    <div class="col-md-12"  v-if="form.item_unit_types.length > 0">
                        <div style="margin:3px" class="table-responsive">
                            <h5 class="separator-title">
                                Listado de Precios
                                <el-tooltip class="item" effect="dark" content="Aplica para realizar compra/venta en presentacion de diferentes precios y/o cantidades" placement="top">
                                    <i class="fa fa-info-circle"></i>
                                </el-tooltip>
                            </h5>
                            <table class="table">
                            <thead>
                            <tr>
                                <th class="text-center">Unidad</th>
                                <th class="text-center">Descripción</th>
                                <th class="text-center">Factor</th>

                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="(row, index) in form.item_unit_types" :key="index">

                                    <td class="text-center">{{row.unit_type.name}}</td>
                                    <td class="text-center">{{row.description}}</td>
                                    <td class="text-center">{{row.quantity_unit}}</td>

                                    <td class="series-table-actions text-right">
                                       <button type="button" class="btn waves-effect waves-light btn-xs btn-success" @click.prevent="selectedPrice(row)">
                                            <i class="el-icon-check"></i>
                                        </button>
                                    </td>


                            </tr>
                            </tbody>
                        </table>

                        </div>

                    </div>
                </div>
            </div>
            <div class="form-actions text-right pt-2">
                <el-button @click.prevent="close()">Cerrar</el-button>
                <el-button v-if="weighted_average_info.show && form.unit_price > 0" type="info" @click="updateSalePrice" size="small">
                    <i class="fa fa-calculator"></i> Calcular PV
                </el-button>
                <el-button type="primary" native-type="submit" :disabled="!form.item_id">{{titleAction}}</el-button>
            </div>
        </form>
        <item-form :showDialog.sync="showDialogNewItem"
                   :external="true"></item-form>

        <lots-form
            :showDialog.sync="showDialogLots"
            :stock="form.quantity"
            :lots="lots"
            @addRowLot="addRowLot">
        </lots-form>

    </el-dialog>
</template>
<style>
.el-select-dropdown {
    max-width: 80% !important;
    margin-right: 5% !important;
}
.input-with-select .el-select .el-input {
    width: 50px;
}
.input-with-select .el-select .el-input .el-input__inner {
    padding-right: 10px;
}

/* Estilos para la información del promedio ponderado */
.weighted-average-info {
    border: 1px solid #e4e7ed;
    border-radius: 4px;
    padding: 10px;
    background-color: #f9f9f9;
}

.weighted-average-info small {
    line-height: 1.5;
    margin-bottom: 2px;
}

.weighted-average-info .text-warning {
    color: #e6a23c !important;
    font-weight: 500;
}

.unit-price-warning {
    border-color: #e6a23c !important;
}
</style>
<script>

    import itemForm from '../../items/form.vue'
    import {calculateRowItem} from '../../../../helpers/functions'
    import LotsForm from '../../items/partials/lots.vue'

    export default {
        props: ['showDialog', 'currencyTypeIdActive', 'exchangeRateSale', 'recordItem'],
        components: {itemForm, LotsForm},
        data() {
            return {
                titleDialog: 'Agregar Producto o Servicio',
                showDialogLots:false,
                resource: 'purchases',
                showDialogNewItem: false,
                errors: {},
                form: {},
                items: [],
                warehouses: [],
                lots: [],
                affectation_igv_types: [],
                system_isc_types: [],
                discount_types: [],
                charge_types: [],
                attribute_types: [],
                use_price: 1,
                lot_code: null,
                change_affectation_igv_type_id: false,
                all_taxes:[],
                taxes:[],
                titleAction: '',
                weighted_average_info: {
                    show: false,
                    weighted_average_cost: 0,
                    last_purchase_price: 0,
                    total_purchases: 0,
                    total_quantity: 0,
                    currency_symbol: 'S/',
                    loading: false
                },
                unit_price_warning: false,
                profit_margin_info: {
                    show: false,
                    percentage: 0,
                    profit: 0
                },
            }
        },
        computed: {
            itemTaxes() {
                return this.taxes.filter(tax => !tax.is_retention);
            },
        },
        created() {
            this.initForm()
            this.$http.get(`/${this.resource}/item/tables`).then(response => {

                this.items = response.data.items
                this.warehouses = response.data.warehouses
                this.taxes = response.data.taxes;
                // this.filterItems()
            })

            this.$eventHub.$on('reloadDataItems', (item_id) => {
                this.reloadDataItems(item_id)
            })
        },
        methods: {
            addRowLot(lots){
                this.lots = lots
            },
            clickLotcode(){
                // if(this.form.stock <= 0)
                //     return this.$message.error('El stock debe ser mayor a 0')

                this.showDialogLots = true
            },
            filterItems(){
                this.items = this.items.filter(item => item.warehouses.length >0)
            },
            initForm() {
                this.errors = {}
                this.form = {
                    item_id: null,
                    warehouse_id: 1,
                    warehouse_description: null,
                    item: {},
                    quantity: 1,
                    unit_price: 0,
                    sale_unit_price: 0,
                    item_unit_types: [],
                    lot_code:null,
                    date_of_due: null,
                    subtotal: null,
                    tax: {},
                    tax_id: null,
                    total: 0,
                    total_tax: 0,
                    type_unit: {},
                    discount: 0,
                    unit_type_id: null,
                    lots: [],
                    discount_type: 'percentage',
                    discount_percentage: 0,
                }

                this.item_unit_type = {};
                this.lots = []
                this.lot_code = null
                
                // Reset weighted average info
                this.weighted_average_info = {
                    show: false,
                    weighted_average_cost: 0,
                    last_purchase_price: 0,
                    total_purchases: 0,
                    total_quantity: 0,
                    currency_symbol: 'S/',
                    loading: false
                };
                this.unit_price_warning = false;
                this.profit_margin_info = {
                    show: false,
                    percentage: 0,
                    profit: 0
                };
            },
            async create() {
                this.titleDialog = (this.recordItem) ? ' Editar Producto o Servicio' : ' Agregar Producto o Servicio';
                this.titleAction = (this.recordItem) ? ' Editar' : ' Agregar';

                if (this.recordItem) {
                    // console.log(this.recordItem)
                    this.form.item_id = await this.recordItem.item_id
                    await this.changeItem()
                    this.form.quantity = this.recordItem.quantity
                    this.form.unit_price = this.recordItem.unit_price
                    this.form.discount_type = this.recordItem.discount_type

                    if(this.form.discount_type == 'percentage') {
                        this.form.discount = this.recordItem.discount_percentage
                    } else {
                        this.form.discount = this.recordItem.discount
                    }
                    
                    // Calcular margen después de cargar los precios
                    this.calculateProfitMargin()
                }
            },
            close() {
                this.initForm()
                this.$emit('update:showDialog', false)
            },
            selectedPrice(row)
            {

                let valor = 0
                switch(row.price_default)
                {
                    case 1:
                        valor = row.price1
                        break
                    case 2:
                         valor = row.price2
                        break
                    case 3:
                         valor = row.price3
                        break

                }

                this.form.item_unit_type_id = row.id
                this.item_unit_type = row

                this.form.unit_price = valor
                this.form.item.unit_type_id = row.unit_type_id
            },
            changeItem() {

                this.form.item = _.find(this.items, {'id': this.form.item_id})
                this.form.unit_price = _.round(this.form.item.purchase_unit_price, 0)
                this.form.sale_unit_price = _.round(this.form.item.sale_unit_price || 0, 0)
                // this.form.affectation_igv_type_id = this.form.item.purchase_affectation_igv_type_id
                this.form.item_unit_types = _.find(this.items, {'id': this.form.item_id}).item_unit_types

                this.form.unit_type_id = this.form.item.unit_type_id
                this.form.tax_id = (this.taxes.length > 0) ? this.form.item.purchase_tax_id: null

                // Cargar información del promedio ponderado
                this.loadWeightedAverageInfo()
                
                // Calcular margen de ganancia si ambos precios están disponibles
                this.calculateProfitMargin()

            },
            async clickAddItem() {

                if(this.form.item.lots_enabled){

                    if(!this.lot_code)
                        return this.$message.error('Código de lote es requerido');

                    if(!this.form.date_of_due)
                        return this.$message.error('Fecha de vencimiento es requerido si lotes esta habilitado.');

                }

                if(this.form.item.series_enabled)
                {

                    if(this.lots.length > this.form.quantity)
                        return this.$message.error('La cantidad de series registradas es superior al stock');

                    if(this.lots.length != this.form.quantity)
                        return this.$message.error('La cantidad de series registradas son diferentes al stock');
                }

                // Validación del precio mínimo basado en promedio ponderado
                if (this.unit_price_warning && this.weighted_average_info.show) {
                    const currentPrice = parseFloat(this.form.unit_price);
                    const averageCost = parseFloat(this.weighted_average_info.weighted_average_cost);
                    
                    try {
                        await this.$confirm(
                            `El precio ingresado (${this.weighted_average_info.currency_symbol}${currentPrice}) está por debajo del promedio ponderado (${this.weighted_average_info.currency_symbol}${averageCost}). ¿Desea continuar?`,
                            'Precio por debajo del promedio',
                            {
                                confirmButtonText: 'Sí, continuar',
                                cancelButtonText: 'Cancelar',
                                type: 'warning'
                            }
                        );
                    } catch (error) {
                        return; // Usuario canceló
                    }
                }

                let date_of_due = this.form.date_of_due

                this.form.tax = _.find(this.taxes, {'id': this.form.tax_id})
                this.form.type_unit = this.form.item.type_unit

                this.form.item.unit_price = this.form.unit_price
                this.form.item.presentation = this.item_unit_type;


                this.form.lot_code = await this.lot_code
                this.form.lots = await this.lots

                this.form = this.changeWarehouse(this.form)

                this.form.date_of_due = date_of_due
                // console.log(this.form)

                if (this.recordItem)
                {
                    this.form.indexi = this.recordItem.indexi
                }

                if(this.form.discount_type == 'percentage') {
                    this.form.discount_percentage = this.form.discount
                }

                // this.initializeFields()
                this.$emit('add', this.form)
                this.initForm()
            },
            changeWarehouse(form){
                let warehouse = _.find(this.warehouses,{'id':this.form.warehouse_id})
                form.warehouse_id = warehouse.id
                form.warehouse_description = warehouse.description
                return form
            },
            reloadDataItems(item_id) {
                this.$http.get(`/${this.resource}/table/items`).then((response) => {
                    this.items = response.data
                    this.form.item_id = item_id
                    this.changeItem()
                    // this.filterItems()

                })
            },
            
            /**
             * Cargar información del promedio ponderado de compras
             */
            async loadWeightedAverageInfo() {
                if (!this.form.item_id) return;
                
                this.weighted_average_info.loading = true;
                this.weighted_average_info.show = false;
                
                try {
                    const response = await this.$http.get(`/purchases/weighted-average-cost/${this.form.item_id}`);
                    
                    if (response.data.success) {
                        this.weighted_average_info = {
                            ...this.weighted_average_info,
                            ...response.data,
                            show: true,
                            loading: false
                        };
                        
                        // Validar el precio actual
                        this.validateUnitPrice();
                    } else {
                        this.weighted_average_info.show = false;
                        this.weighted_average_info.loading = false;
                    }
                } catch (error) {
                    console.error('Error cargando promedio ponderado:', error);
                    this.weighted_average_info.show = false;
                    this.weighted_average_info.loading = false;
                }
            },
            
            /**
             * Validar precio unitario contra el promedio ponderado
             */
            validateUnitPrice() {
                if (!this.weighted_average_info.show || !this.form.unit_price) {
                    this.unit_price_warning = false;
                } else {
                    const currentPrice = parseFloat(this.form.unit_price);
                    const averageCost = parseFloat(this.weighted_average_info.weighted_average_cost);
                    
                    // Mostrar advertencia si el precio está por debajo del promedio ponderado
                    this.unit_price_warning = currentPrice < averageCost && averageCost > 0;
                }
                
                // Recalcular margen de ganancia al cambiar precio de compra
                this.calculateProfitMargin();
                
                // Opcional: Prevenir precios por debajo del promedio (descomenta si deseas esta funcionalidad)
                // if (this.unit_price_warning) {
                //     this.$message.warning(`El precio está por debajo del promedio ponderado (${this.weighted_average_info.currency_symbol}${averageCost})`);
                // }
            },
            
            /**
             * Actualizar precio de venta basado en el costo
             */
            async updateSalePrice() {
                if (!this.form.item_id || !this.form.unit_price) {
                    this.$message.error('Seleccione un producto y establezca un precio de compra');
                    return;
                }
                
                try {
                    // Mostrar diálogo para confirmar el margen de ganancia
                    const { value: profitMargin } = await this.$prompt('Ingrese el porcentaje de ganancia deseado:', 'Actualizar Precio de Venta', {
                        confirmButtonText: 'Actualizar',
                        cancelButtonText: 'Cancelar',
                        inputValue: '30',
                        inputPattern: /^\d+(\.\d{1,2})?$/,
                        inputErrorMessage: 'Ingrese un porcentaje válido'
                    });
                    
                    if (!profitMargin) return;
                    
                    const purchasePrice = parseFloat(this.form.unit_price);
                    const margin = parseFloat(profitMargin);
                    const newSalePrice = purchasePrice * (1 + (margin / 100));
                    
                    // Confirmar la actualización
                    const confirmResult = await this.$confirm(
                        `¿Confirma actualizar el precio de venta a ${this.weighted_average_info.currency_symbol}${newSalePrice.toFixed(2)}?`,
                        'Confirmar Actualización',
                        {
                            confirmButtonText: 'Sí, actualizar',
                            cancelButtonText: 'Cancelar',
                            type: 'question'
                        }
                    );
                    
                    if (confirmResult !== 'confirm') return;
                    
                    // Actualizar en el backend
                    const response = await this.$http.post('/purchases/update-sale-price', {
                        item_id: this.form.item_id,
                        purchase_price: purchasePrice,
                        profit_margin: margin
                    });
                    
                    if (response.data.success) {
                        this.$message.success(`Precio de venta actualizado: ${this.weighted_average_info.currency_symbol}${response.data.data.sale_unit_price}`);
                        
                        // Actualizar el item en el formulario si es necesario
                        if (this.form.item) {
                            this.form.item.sale_unit_price = response.data.data.sale_unit_price;
                            this.form.item.purchase_unit_price = response.data.data.purchase_unit_price;
                            this.form.item.percentage_of_profit = response.data.data.percentage_of_profit;
                        }
                    } else {
                        this.$message.error(response.data.message || 'Error al actualizar el precio de venta');
                    }
                    
                } catch (error) {
                    if (error === 'cancel') {
                        // Usuario canceló la operación
                        return;
                    }
                    console.error('Error actualizando precio de venta:', error);
                    this.$message.error('Error al actualizar el precio de venta');
                }
            },
            
            /**
             * Calcular precio de venta sugerido basado en un margen
             */
            async calculateSuggestedSalePrice() {
                if (!this.form.unit_price || this.form.unit_price <= 0) {
                    this.$message.error('Establezca primero un precio de compra válido');
                    return;
                }
                
                try {
                    const { value: profitMargin } = await this.$prompt('Ingrese el porcentaje de ganancia deseado:', 'Calcular Precio de Venta', {
                        confirmButtonText: 'Calcular',
                        cancelButtonText: 'Cancelar',
                        inputValue: '30',
                        inputPattern: /^\d+(\.\d{1,2})?$/,
                        inputErrorMessage: 'Ingrese un porcentaje válido'
                    });
                    
                    if (!profitMargin) return;
                    
                    const purchasePrice = parseFloat(this.form.unit_price);
                    const margin = parseFloat(profitMargin);
                    const suggestedPrice = purchasePrice * (1 + (margin / 100));
                    
                    this.form.sale_unit_price = suggestedPrice.toFixed(2);
                    this.calculateProfitMargin();
                    
                    this.$message.success(`Precio sugerido: ${this.form.item.currency_type_symbol || 'S/'}${suggestedPrice.toFixed(2)}`);
                    
                } catch (error) {
                    // Usuario canceló
                }
            },
            
            /**
             * Validar y calcular margen al cambiar precio de venta
             */
            validateSalePrice() {
                this.calculateProfitMargin();
            },
            
            /**
             * Calcular información del margen de ganancia
             */
            calculateProfitMargin() {
                const purchasePrice = parseFloat(this.form.unit_price) || 0;
                const salePrice = parseFloat(this.form.sale_unit_price) || 0;
                
                if (purchasePrice > 0 && salePrice > 0) {
                    const profit = salePrice - purchasePrice;
                    const percentage = ((profit / purchasePrice) * 100);
                    
                    this.profit_margin_info = {
                        show: true,
                        percentage: percentage.toFixed(2),
                        profit: profit.toFixed(2)
                    };
                } else {
                    this.profit_margin_info.show = false;
                }
            },
            
            /**
             * Actualizar precio de venta en el item cuando se pierde el foco
             */
            async updateItemSalePrice() {
                if (!this.form.item_id || !this.form.sale_unit_price) return;
                
                try {
                    const response = await this.$http.post('/purchases/update-sale-price', {
                        item_id: this.form.item_id,
                        sale_unit_price: parseFloat(this.form.sale_unit_price),
                        purchase_price: parseFloat(this.form.unit_price) || 0
                    });
                    
                    if (response.data.success) {
                        // Actualizar el item en memoria
                        if (this.form.item) {
                            this.form.item.sale_unit_price = response.data.data.sale_unit_price;
                            this.form.item.purchase_unit_price = response.data.data.purchase_unit_price || this.form.item.purchase_unit_price;
                            this.form.item.percentage_of_profit = response.data.data.percentage_of_profit;
                        }
                        
                        // Actualizar también en la lista de items para futuras selecciones
                        const itemIndex = this.items.findIndex(item => item.id === this.form.item_id);
                        if (itemIndex !== -1) {
                            this.items[itemIndex].sale_unit_price = response.data.data.sale_unit_price;
                            this.items[itemIndex].percentage_of_profit = response.data.data.percentage_of_profit;
                        }
                        
                        this.$message.success('Precio de venta actualizado correctamente');
                    }
                } catch (error) {
                    console.error('Error actualizando precio de venta:', error);
                    this.$message.error('Error al actualizar el precio de venta');
                }
            },
        }
    }

</script>
