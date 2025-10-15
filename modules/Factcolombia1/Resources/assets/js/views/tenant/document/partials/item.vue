<template>
    <el-dialog :title="titleDialog" :visible="showDialog" @open="create" @close="close" top="7vh" :close-on-click-modal="false">
        <form autocomplete="off" @submit.prevent="clickAddItem">
            <div class="form-body">
                <div class="row">
                    <div class="col-md-7 col-lg-7 col-xl-7 col-sm-7">
                        <div class="form-group" id="custom-select" :class="{'has-danger': errors.item_id}">
                            <label class="control-label">
                                Producto/Servicio
                                <a v-if="typeUser != 'seller'" href="#" @click.prevent="showDialogNewItem = true">[+ Nuevo]</a>
                            </label>

                            <!-- Checkbox para activar la búsqueda por código de barras -->
                            <template v-if="!is_client">
                                <el-checkbox v-model="search_item_by_barcode" :disabled="recordItem != null">Buscar por código de barras</el-checkbox><br>
                            </template>

                            <!-- Input para el escáner de código de barras, visible solo si search_item_by_barcode es true -->
                            <div v-if="search_item_by_barcode">
                                <div class="form-group">
                                    <label for="barcodeInput">Escáner de Código de Barras</label>
                                    <input type="text" id="barcodeInput" v-model="barcodeInput" @keyup.enter="handleBarcodeScan" placeholder="Escanea el código de barras aquí" class="form-control">
                                </div>
                                <!-- Muestra el nombre del producto escaneado -->
                                <div v-if="currentProduct">
                                    <label>Producto seleccionado:</label>
                                    <p>{{ currentProduct.full_description }}</p>
                                </div>
                            </div>

                            <template v-if="!search_item_by_barcode" id="select-append">
                                <el-input id="custom-input">
                                    <el-select :disabled="recordItem != null"
                                            v-model="form.item_id"
                                            @change="changeItem"
                                            filterable
                                            remote
                                            placeholder="Buscar"
                                            popper-class="el-select-items"
                                            @visible-change="focusTotalItem"
                                            slot="prepend"
                                            id="select-width"
                                            :remote-method="searchRemoteItems"
                                            :loading="loading_search">
                                        <el-tooltip v-for="option in items"  :key="option.id" placement="top">
                                            <div slot="content">
                                                Marca: {{option.brand}} <br>
                                                Categoria: {{option.category}} <br>
                                                Stock: {{option.stock}} <br>
                                                Precio: {{option.currency_type_symbol}} {{option.sale_unit_price}} <br>
                                            </div>
                                            <el-option  :value="option.id" :label="option.full_description"></el-option>
                                        </el-tooltip>
                                    </el-select>
                                    <el-tooltip slot="append" class="item" effect="dark" content="Ver Stock del Producto" placement="bottom" :disabled="recordItem != null">
                                        <el-button :disabled="isEditItemNote"  @click.prevent="clickWarehouseDetail()"><i class="fa fa-search"></i></el-button>
                                    </el-tooltip>
                                </el-input>
                            </template>

                            <!-- Selector de productos, visible solo si search_item_by_barcode es false
                            <el-input id="custom-input" v-else>
                                <el-select :disabled="recordItem != null"
                                        v-model="form.item_id"
                                        @change="changeItem"
                                        filterable
                                        remote
                                        placeholder="Buscar producto por nombre"
                                        popper-class="el-select-items"
                                        @visible-change="focusTotalItem"
                                        slot="prepend"
                                        id="select-width"
                                        :remote-method="searchRemoteItems"
                                        :loading="loading_search">
                                    <el-tooltip v-for="option in currentSearchItems" :key="option.id" placement="top">
                                        <div slot="content">
                                            Marca: {{option.brand}} <br>
                                            Categoria: {{option.category}} <br>
                                            Stock: {{option.stock}} <br>
                                            Precio: {{option.currency_type_symbol}} {{option.sale_unit_price}} <br>
                                        </div>
                                        <el-option :value="option.id" :label="option.full_description"></el-option>
                                    </el-tooltip>
                                </el-select>
                                <el-tooltip slot="append" class="item" effect="dark" content="Ver Stock del Producto" placement="bottom" :disabled="recordItem != null">
                                    <el-button :disabled="isEditItemNote" @click.prevent="clickWarehouseDetail()"><i class="fa fa-search"></i></el-button>
                                </el-tooltip>
                            </el-input> -->
                            <small class="form-control-feedback" v-if="errors.item_id" v-text="errors.item_id[0]"></small>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="form-group" :class="{'has-danger': errors.tax_id}">
                            <label class="control-label">Impuesto</label>
                            <a href="#" class="control-label" @click="form.tax_id = null"> [ * Excluido]</a>
                            <el-select v-model="form.tax_id"  filterable>
                                <el-option v-for="option in itemTaxes" :key="option.id" :value="option.id" :label="option.name"></el-option>
                            </el-select>
                            <!-- <el-checkbox :disabled="recordItem != null" v-model="change_tax_id">Editar</el-checkbox> -->
                            <template v-if="!is_client">
                            <el-checkbox
                                v-model="tax_included_in_price"
                                :disabled="false"
                                @change="change_price_tax_included">
                                {{ taxCheckboxLabel }}
                            </el-checkbox><br>
                            </template>
                            <small class="form-control-feedback" v-if="errors.tax_id" v-text="errors.tax_id[0]"></small>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group" :class="{'has-danger': errors.notes}">
                            <label class="control-label">Notas</label>
                            <el-input v-model="form.notes"></el-input>
                            <small class="form-control-feedback" v-if="errors.notes" v-text="errors.notes[0]"></small>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-3">
                        <div class="form-group" :class="{'has-danger': errors.quantity}">
                            <label class="control-label">Cantidad</label>
                            <el-input-number v-model="form.quantity" :min="0.01" :disabled="form.item.calculate_quantity"></el-input-number>
                            <small class="form-control-feedback" v-if="errors.quantity" v-text="errors.quantity[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-3">
                        <div class="form-group" :class="{'has-danger': errors.price}">
                            <label class="control-label">Precio Unitario</label>
                            <el-input v-model="form.price" @input="calculateQuantity" :readonly="typeUser === ''">
                                <template slot="prepend" v-if="currencyTypeSymbolActive">{{ currencyTypeSymbolActive }}</template>
                            </el-input>
                            <small class="form-control-feedback" v-if="errors.price" v-text="errors.unit_price[0]"></small>
                        </div>
                    </div>

                    <div style="padding-top: 1%;" class="col-md-2 col-sm-2" v-if="form.item_id && form.item.lots_enabled && form.lots_group.length > 0">
                        <a href="#"  class="text-center font-weight-bold text-info" @click.prevent="clickLotGroup">[&#10004; Seleccionar lote]</a>
                    </div>

                    <div style="padding-top: 1%;" class="col-md-3 col-sm-3" v-if="form.item_id && form.item.series_enabled">
                        <!-- <el-button type="primary" native-type="submit" icon="el-icon-check">Elegir serie</el-button> -->
                        <a href="#"  class="text-center font-weight-bold text-info" @click.prevent="clickSelectLots">[&#10004; Seleccionar series]</a>
                    </div>

                    <div class="col-md-3 col-sm-6" v-show="form.item.calculate_quantity">
                        <div class="form-group"  :class="{'has-danger': errors.total_item}">
                            <label class="control-label">Total venta producto</label>
                            <el-input v-model="total_item" @input="calculateQuantity" :min="0.01" ref="total_item">
                                <template slot="prepend" v-if="currencyTypeSymbolActive">{{ currencyTypeSymbolActive }}</template>
                            </el-input>
                            <small class="form-control-feedback" v-if="errors.total_item" v-text="errors.total_item[0]"></small>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <div class="form-group"  :class="{'has-danger': errors.discount}">
                            <label class="control-label">Descuento</label>
                            <el-input v-model="form.discount" :min="0" >
                                <template slot="prepend" v-if="currencyTypeSymbolActive">{{ currencyTypeSymbolActive }}</template>
                            </el-input>
                            <small class="form-control-feedback" v-if="errors.discount" v-text="errors.discount[0]"></small>
                        </div>
                    </div>

                    <template v-if="!is_client">
                        <div class="col-md-12"  v-if="form.item_unit_types.length > 0">
                            <div style="margin:3px" class="table-responsive">
                                <h5 class="separator-title">
                                    Lista de Precios
                                    <el-tooltip class="item" effect="dark" content="Aplica para realizar venta en presentacion de diferentes precios y/o cantidades" placement="top">
                                        <i class="fa fa-info-circle"></i>
                                    </el-tooltip>
                                </h5>
                                <table class="table">
                                <thead>
                                <tr>
                                    <th class="text-center">Unidad</th>
                                    <th class="text-center">Descripción</th>
                                    <th class="text-center">Factor</th>
                                    <th class="text-center">Precio 1</th>
                                    <th class="text-center">Precio 2</th>
                                    <th class="text-center">Precio 3</th>
                                    <th class="text-center">Precio Default</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="(row, index) in form.item_unit_types" :key="index">

                                        <td class="text-center">{{row.unit_type.name}}</td>
                                        <td class="text-center">{{row.description}}</td>
                                        <td class="text-center">{{row.quantity_unit}}</td>
                                        <td class="text-center">{{row.price1}}</td>
                                        <td class="text-center">{{row.price2}}</td>
                                        <td class="text-center">{{row.price3}}</td>
                                        <td class="text-center">Precio {{row.price_default}}</td>
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

                    </template>
                </div>
            </div>
            <div class="form-actions text-right pt-2">
                <el-button @click.prevent="close()">Cerrar</el-button>
                <el-button class="add" type="primary" native-type="submit" v-if="form.item_id">{{titleAction}}</el-button>
            </div>
        </form>

        <item-form :showDialog.sync="showDialogNewItem"
                   :external="true"></item-form>


        <warehouses-detail
                :showDialog.sync="showWarehousesDetail"
                :isUpdateWarehouseId="isUpdateWarehouseId"
                :warehouses="warehousesDetail">
            </warehouses-detail>

        <lots-group
            :quantity="form.quantity"
            :showDialog.sync="showDialogLots"
            :lots_group="form.lots_group"
            @addRowLotGroup="addRowLotGroup">
        </lots-group>

        <select-lots-form
            :showDialog.sync="showDialogSelectLots"
            :lots="lots"
            @addRowSelectLot="addRowSelectLot">
        </select-lots-form>


    </el-dialog>
</template>
<style>
.el-select-dropdown {
    max-width: 80% !important;
    margin-right: 5% !important;
}
</style>

<script>

    import ItemForm from '@views/items/form.vue'
    import LotsGroup from './inventory/lots_group.vue'

    import WarehousesDetail from './inventory/select_warehouses.vue'
    import SelectLotsForm from './inventory/lots.vue'


    export default {
        props: ['recordItem','showDialog', 'operationTypeId', 'currencyTypeIdActive', 'currencyTypeSymbolActive', 'exchangeRateSale', 'typeUser', 'isEditItemNote', 'configuration'],
        components: {ItemForm, LotsGroup, WarehousesDetail, SelectLotsForm},
        data() {
            return {
                loading_search:false,
                titleAction: '',
                is_client:false,
                titleDialog: '',
                resource: 'co-documents',
                showDialogNewItem: false,
                has_list_prices: false,
                errors: {},
                form: {},
                all_items: [],
                items: [],
                operation_types: [],
                all_affectation_igv_types: [],
                affectation_igv_types: [],
                system_isc_types: [],
                discount_types: [],
                charge_types: [],
                attribute_types: [],
                use_price: 1,
                change_affectation_igv_type_id: false,
                activePanel: 0,
                total_item: 0,
                item_unit_types: [],
                showWarehousesDetail: false,
                warehousesDetail:[],
                showListStock:false,
                search_item_by_barcode:false,
                tax_included_in_price: false,
                isUpdateWarehouseId:null,
                showDialogLots: false,
                showDialogSelectLots: false,
                lots:[],
                all_taxes:[],
                taxes:[],
                items_aiu: [],
                barcodeInput: '', // Agrega esto para almacenar la entrada del código de barras
                currentSearchItems: [], //carga productos actuales en la busqueda
                localConfiguration: null,

            }
        },
        computed: {
            //mostrar el nombre del producto cuando se busca por codigo de barras
            currentProduct() {
                return this.items.find(item => item.id === this.form.item_id);
            },
            itemTaxes() {
                return this.taxes.filter(tax => !tax.is_retention);
            },
            retentiontaxes() {
                return this.taxes.filter(tax => tax.is_retention);
            },
            retentionSelected() {
            if (this.retention.retention_id == null) return { rate: 0 };

                return this.taxes.find(row => row.id == this.retention.retention_id);
            },
            showTotalAndSave() {
                return (
                this.document.hasOwnProperty("items") && this.document.items.length > 0
            );
            },

            typeNoteDocuments() {
                return this.typeDocuments.filter(row => row.id != 1);
            },

            taxCheckboxLabel() {
                // Si la configuración ya está cargada...
                if (this.localConfiguration && typeof this.localConfiguration.item_tax_included !== 'undefined') {
                // Si item_tax_included es false: el precio actual ya trae IVA incluido.
                // Si es true: el precio base no incluye IVA (se sumará IVA en otro proceso).
                return this.localConfiguration.item_tax_included
                        ? "Precio más IVA"
                        : "Impuesto incluido en el precio.";
                }
                // Valor por defecto en caso de no tener la configuración (puedes ajustar este fallback)
                return "Impuesto incluido en el precio.";
            }
        },
        created() {
            this.initForm()
            this.$http.get(`/${this.resource}/item/tables`).then(response => {
               // console.log('tablas new edit')
                this.taxes = response.data.taxes;
                this.all_items = response.data.items
                this.items_aiu = response.data.items_aiu
                this.filterItems()

            })

            this.$eventHub.$on('reloadDataItems', (item_id) => {
                this.reloadDataItems(item_id)
            })

            this.$eventHub.$on('selectWarehouseId', (warehouse_id) => {
                // console.log(warehouse_id)
                this.form.warehouse_id = warehouse_id
            })

              // Petición a configuración avanzada usando .then() en lugar de async/await
            this.$http.get('/co-advanced-configuration/record')
                .then(response => {
                this.localConfiguration = response.data.data;
                })
                .catch(error => {
                console.error("Error al obtener la configuración avanzada:", error);
                });
        },
        methods: {
            // Método que se llama cuando se escanea un código de barras
            async handleBarcodeScan() {
                    if (this.barcodeInput) {
                        await this.searchRemoteItems(this.barcodeInput);
                        const foundItem = this.items.find(item => item.internal_id === this.barcodeInput);

                        if (foundItem) {
                            // Si encuentra un ítem, actualiza form.item_id y lo añade a currentSearchItems si es necesario
                            this.form.item_id = foundItem.id;
                            if (!this.currentSearchItems.some(item => item.id === foundItem.id)) {
                                this.currentSearchItems.push(foundItem);
                            }
                            this.changeItem();
                        } else {
                            // Si no encuentra el producto, abre el modal para crear un nuevo producto
                            this.showDialogNewItem = true;
                        }

                        this.barcodeInput = ''; // Limpia el campo después del escaneo
                    }
            },
            async searchRemoteItems(input) {
                if (input.length > 2) {
                    this.loading_search = true;
                    let parameters = `input=${input}`;

                    await this.$http.get(`/${this.resource}/search-items/?${parameters}`)
                        .then(response => {
                            // Actualiza currentSearchItems con los resultados de la búsqueda
                            this.currentSearchItems = response.data.items;

                            // Además, actualiza la lista principal de ítems si es necesario
                            let itemMap = new Map();

                            // Añade los ítems encontrados al mapa para evitar duplicados
                            this.currentSearchItems.forEach(item => itemMap.set(item.id, item));

                            // Añade los ítems existentes al mapa, si no están ya presentes
                            this.items.forEach(item => {
                                if (!itemMap.has(item.id)) {
                                    itemMap.set(item.id, item);
                                }
                            });

                            // Actualiza this.items con todos los ítems, incluyendo los buscados
                            this.items = Array.from(itemMap.values());

                            this.loading_search = false;
                        });
                } else {
                    // Si no hay suficiente entrada para una búsqueda, se podrían mostrar todos los ítems o manejar de otra manera
                    this.currentSearchItems = [...this.items];
                }
            },
            filterItems() {
                this.items = this.all_items
            },
            RateSelectedTax(tax_id) {
                if(tax_id != null)
                    return this.taxes.find(row => row.id == tax_id).rate;
                else
                    return 0
            },
            enabledSearchItemsBarcode(){

                if(this.search_item_by_barcode){

                    if (this.items.length == 1){

                        this.form.item_id = this.items[0].id
                        this.changeItem()
                    }
                }
            },
            filterMethod(query){

                let item = _.find(this.items, {'internal_id': query});

                if(item){
                    this.form.item_id = item.id
                    this.changeItem()
                }
                // console.log(item)
            },
            clickWarehouseDetail(){

                if(!this.form.item_id){
                    return this.$message.error('Seleccione un item');
                }

                let item = _.find(this.items, {'id': this.form.item_id});

                this.warehousesDetail = item.warehouses
                this.showWarehousesDetail = true
            },
            // filterItems(){
            //     this.items = this.items.filter(item => item.warehouses.length >0)
            // },
            initForm() {
                this.errors = {};

                this.form = {
                    id: null,
                    item_id: null,
                    item: {},
                    code: null,
                    discount: 0,
                    name: null,
                    price: null,
                    quantity: null,
                    notes: null,
                    subtotal: null,
                    tax: {},
                    tax_id: null,
                    total: 0,
                    total_tax: 0,
                    type_unit: {},
                    unit_type_id: null,
                    item_unit_types: [],
                    IdLoteSelected: null,
                };

                this.activePanel = 0;
                this.total_item = 0;
                this.item_unit_type = {};
                this.has_list_prices = false;
                this.tax_included_in_price = true;
            },

            async create() {
                this.titleDialog = (this.recordItem) ? ' Editar Producto o Servicio' : ' Agregar Producto o Servicio';
                this.titleAction = (this.recordItem) ? ' Editar' : ' Agregar';
                // let operation_type = await _.find(this.operation_types, {id: this.operationTypeId})
                // this.affectation_igv_types = await _.filter(this.all_affectation_igv_types, {exportation: operation_type.exportation})

                if (this.recordItem) {
                    // Aquí asignas el ID del ítem a editar en form.item_id
                    this.form.item_id = this.recordItem.item_id;
                    await this.changeItem()

                    this.form.tax_id = this.recordItem.tax_id
                    this.form.quantity = this.recordItem.quantity
                    this.form.notes = this.recordItem.notes
                    this.form.price = this.recordItem.price
                    this.form.discount = this.recordItem.discount

                    this.form.warehouse_id = this.recordItem.warehouse_id
                    this.isUpdateWarehouseId = this.recordItem.warehouse_id

                    if(this.isEditItemNote){
                        this.form.item.currency_type_id = this.currencyTypeIdActive
                        this.form.item.currency_type_symbol = this.currencyTypeSymbolActive
                    }
                    this.calculateQuantity()
                }else{
                    this.isUpdateWarehouseId = null
                }
            },

            close() {
                this.initForm()
                this.$emit('update:showDialog', false)
            },
            async changeItem() {
                // Busca el ítem en la lista de ítems disponibles por su ID
                this.form.item = this.items.find(item => item.id === this.form.item_id);

                    // Añade el ítem a editar a currentSearchItems si no está presente
                    if (!this.currentSearchItems.some(item => item.id === this.form.item_id)) {
                    const itemToEdit = this.items.find(item => item.id === this.form.item_id);
                    if (itemToEdit) {
                        this.currentSearchItems.push(itemToEdit);
                    }
                }

                if (this.form.item) {
                    // Si se encuentra el ítem, actualiza los detalles en el formulario
                    this.form.item_unit_types = this.form.item.item_unit_types;
                    this.form.id = this.form.item_id;
                    this.form.unit_type_id = this.form.item.unit_type_id;
                    this.lots = this.form.item.lots;
                    this.form.tax_id = (this.taxes.length > 0) ? this.form.item.tax.id : null;
                    this.form.price = this.form.item.sale_unit_price;
                    this.form.quantity = 1;
                    this.cleanTotalItem();
                    this.showListStock = true;
                    this.form.lots_group = this.form.item.lots_group;
                } else {
                    // Maneja el caso en que el ítem no se encuentra
                    console.log("Ítem no encontrado para la edición");
                }
            },

            getItemsAiu(detailAiu)
            {
                const context = this

                let items_a = this.items_aiu.filter( row => row.internal_id == 'aiu00001' || row.internal_id == 'aiu00002' || row.internal_id == 'aiu00003' )

                let data = items_a.map(row => {

                    let formaiu = context.getFormAiu()
                    const price = context.getPriceAiu(row.internal_id, detailAiu)

                    formaiu.item_id = row.id
                    formaiu.item = row

                    formaiu.unit_type_id = formaiu.item.unit_type_id
                    formaiu.item.sale_unit_price = price
                    formaiu.item_unit_types = _.find(context.items_aiu, {'id': formaiu.item_id}).item_unit_types
                    formaiu.id = formaiu.item_id
                    formaiu.tax_id = (context.taxes.length > 0) ? formaiu.item.tax.id: null
                    formaiu.price = price
                    formaiu.quantity = 1
                    formaiu.item.presentation = {};

                    formaiu.tax = _.find(context.taxes, {'id': formaiu.tax_id})

                    return formaiu

                })

                return data
            },
            getPriceAiu(internal_id, detailAiu)
            {
                const context = this
                let price = 0

                switch(internal_id)
                {
                    case 'aiu00001':
                        price = detailAiu.value_administartion
                    break;
                    case 'aiu00002':
                        price = detailAiu.value_sudden
                    break;
                    case 'aiu00003':
                        price = detailAiu.value_utility
                    break;
                }
                return price
            },
            getFormAiu()
            {
                return {
                    id: null,
                    item_id: null,
                    item: {},
                    code: null,
                    discount: 0,
                    name: null,
                    price: null,
                    quantity: null,
                    notes: null,
                    subtotal: null,
                    tax: {},
                    tax_id: null,
                    total: 0,
                    total_tax: 0,
                    type_unit: {},
                    unit_type_id: null,
                    item_unit_types: [],
                };
            },

            focusTotalItem(change) {
                if(!change && this.form.item.calculate_quantity) {
                    this.$refs.total_item.$el.getElementsByTagName('input')[0].focus()
                    this.total_item = this.form.unit_price_value
                }
            },

            calculateQuantity() {
                // debugger
                if(this.form.item.calculate_quantity) {
                    //console.log('entro')
                    this.form.quantity = _.round((this.total_item / this.form.price), 4)
                }
            },

            cleanTotalItem(){
                this.total_item = null
            },

            async clickAddItem() {
                if(this.form.item.lots_enabled){
                    if(!this.form.IdLoteSelected)
                        return this.$message.error('Debe seleccionar un lote.');
                }
                if (this.validateTotalItem().total_item)
                    return;
                
                // Validación: Precio de venta no puede ser menor al precio de compra
                if (this.form.item.purchase_unit_price && parseFloat(this.form.price) < parseFloat(this.form.item.purchase_unit_price)) {
                    return this.$message.error(`El precio de venta (${this.form.price}) no puede ser menor al precio de compra (${this.form.item.purchase_unit_price}). Por favor ajuste el precio.`);
                }

                // Validación de stock
                try {
                    const stockResponse = await this.$http.post('/co-documents/validate-stock', {
                        item_id: this.form.item_id,
                        quantity: this.form.quantity
                    });
                    
                    if (!stockResponse.data.success) {
                        return this.$message.error(stockResponse.data.message);
                    }
                } catch (error) {
                    if (error.response && error.response.data && error.response.data.message) {
                        return this.$message.error(error.response.data.message);
                    }
                    return this.$message.error('Error al validar el stock del producto.');
                }
                
                if(null === this.form.tax_id)
                    this.form.tax = {'code': "ZZ", 'conversion': "100.00", 'id': 0, 'in_base': false, 'in_tax': null, 'is_fixed_value': false, 'is_percentage': true, 'is_retention': false, 'name': "EXCLUIDO", 'rate': "0.00", 'retention': 0, 'total': 0, 'type_tax': {'code': "ZZ", 'description': "Articulos Excluidos de Impuesto", 'id': 99, 'name': "EXCLUIDO"}}
                else
                    this.form.tax = _.find(this.taxes, {'id': this.form.tax_id})
                this.form.type_unit = this.form.item.type_unit
                this.form.item.presentation = this.item_unit_type;
                if (this.recordItem){
                    this.form.indexi = this.recordItem.indexi
                }
                let IdLoteSelected = this.form.IdLoteSelected
                let select_lots = await _.filter(this.form.item.lots, {'has_sale':true})
                let un_select_lots = await _.filter(this.form.item.lots, {'has_sale':false})
                if(this.form.item.series_enabled){
                    if(select_lots.length != this.form.quantity)
                        return this.$message.error('La cantidad de series seleccionadas son diferentes a la cantidad a vender');
                }
                this.form.IdLoteSelected = IdLoteSelected
//                console.log(this.form)
                this.$emit('add', this.form);
                if (this.recordItem){
                    this.close()
                }
                // Agrega estas líneas al final del método clickAddItem
                this.initForm(); // Reinicia el formulario
                this.$nextTick(() => {
                    if (this.$refs.barcodeInput) {
                    this.$refs.barcodeInput.focus(); // Mueve el foco al campo de entrada del código de barras
                    }
                });
                // let unit_price = (this.form.has_igv)?this.form.unit_price_value:this.form.unit_price_value*1.18;
                // this.form.input_unit_price_value = this.form.unit_price_value;
                // this.form.unit_price = unit_price;
                // this.form.item.unit_price = unit_price;
                // this.row = calculateRowItem(this.form, this.currencyTypeIdActive, this.exchangeRateSale);
                // this.row.edit = false;
                //this.initializeFields()
            },

            validateTotalItem(){
                this.errors = {}
                if(this.form.item.calculate_quantity){
                    if(this.total_item < 0.01)
                        this.$set(this.errors, 'total_item', ['total venta item debe ser mayor a 0.01']);
                }
                return this.errors
            },

            reloadDataItems(item_id) {
                if(!item_id){
                    this.$http.get(`/${this.resource}/table/items`).then((response) => {
                        this.items = response.data
                        this.form.item_id = item_id
                    })
                }
                else{
                    this.$http.get(`/${this.resource}/search/item/${item_id}`).then((response) => {
                        this.items = response.data.items
                        this.form.item_id = item_id
                        this.changeItem()
                    })
                }
            },

            change_price_tax_included()
            {
                if(parseFloat(this.form.price) == 0){
                    if(this.tax_included_in_price)
                        this.form.price = this.form.item.sale_unit_price * (1 + (this.RateSelectedTax(this.form.tax_id) / 100))
                    else
                        this.form.price = this.form.item.sale_unit_price
                }
                else{
                    if(parseFloat(this.form.price) > 0){

                        if(this.tax_included_in_price)
                            this.form.price = this.form.price * (1 + (this.RateSelectedTax(this.form.tax_id) / 100))
                        else
                            this.form.price = this.form.price / (1 + (this.RateSelectedTax(this.form.tax_id) / 100))
                    }
                }
            },
            selectedPrice(row)
            {

                // console.log(row)
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

                this.form.price = valor
                this.form.item.unit_type_id = row.unit_type_id
                // this.form.quantity = row.quantity_unit
                this.calculateQuantity()
                // console.log(this.form)
            },
            addRowLotGroup(id)
            {
                this.form.IdLoteSelected =  id
            },
            clickLotGroup()
            {
                this.showDialogLots = true
            },
            async clickSelectLots(){
                this.showDialogSelectLots = true
            },
            addRowSelectLot(lots){
                this.lots = lots
            },
        }
    }

</script>
