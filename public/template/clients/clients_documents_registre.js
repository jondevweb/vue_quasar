appVM.showPointcollecteSelector(true)
currentComponentConfig = {
    el: '#TOTORO',
    data: function () {
        return {
            rsds: {
                available: [],
                rows: [],
                pagination: {
                    rowsPerPage: 0,
                    /*rowsNumber: rows.value.length*/
                },
                visibleColumns: ['date_collecte', 'dechet', 'poids', 'unite', 'nom_adresse_destination','destination_finale_traitement'],
                columns: [{
                    name: 'date_collecte',
                    required: true,
                    label: 'Date de collecte',
                    align: 'center',
                    field: 'date_collecte',
                    sortable: true,
                    sort: (a, b, rowA, rowB) => {if (a==b) return 0;return moment(a, 'DD/MM/YYYY').isBefore(moment(b, 'DD/MM/YYYY')) ? 1 : -1 },
                    },
                    {
                    name: 'dechet',
                    required: true,
                    label: 'Nature du déchet',
                    align: 'center',
                    field: 'dechet',
                    sortable: true,
                    },
                    {
                    name: 'code_dechet',
                    label: 'Code du déchet',
                    align: 'center',
                    field: 'code_dechet',
                    sortable: true,
                    },
                    {
                    name: 'quantite',
                    required: true,
                    label: 'Quantité',
                    align: 'center',
                    field: 'quantite',
                    sortable: true,
                    },
                    {
                    name: 'unite',
                    required: true,
                    label: 'Unité',
                    align: 'center',
                    field: 'unite',
                    sortable: true,
                    },
                    {
                    name: 'numero_bsd',
                    label: 'N° BSD',
                    align: 'center',
                    field: 'numero_bsd',
                    sortable: true,
                    },
                    {
                    name: 'nom_adresse_transporteur',
                    label: 'Nom + adresse du transporteur',
                    align: 'center',
                    field: 'nom_adresse_transporteur',
                    sortable: true,
                    },
                    {
                    name: 'transporteur_entree_recepisse',
                    label: 'N° Récépissé',
                    align: 'center',
                    field: 'transporteur_entree_recepisse',
                    sortable: true,
                    },
                    {
                    name: 'nom_adresse_destination',
                    label: 'Nom + adresse destination',
                    align: 'center',
                    field: 'nom_adresse_destination',
                    sortable: true,
                    },
                    {
                    name: 'destination_finale_traitement',
                    label: 'Traitement final du déchet',
                    align: 'center',
                    field: 'destination_finale_traitement',
                    sortable: true,
                    },
                    {
                    name: 'destination_regroupement_code_traitement',
                    label: 'Code du traitement',
                    align: 'center',
                    field: 'destination_regroupement_code_traitement',
                    sortable: true,
                    },
                ]
                },
        }
    },
    computed: {
    },
    watch: {
        dayOfTheWeek: function(newVal, oldVal) {
            var vm = this
            vm.loadRsds()
        }
    },
    methods: {
        getColumn: function(name) {
            var vm = this
            return _.find(vm.rsds.columns, { name: name });
        },
        loadRsds: function() {
            var vm = this

            post('/api/v1.0/client/pointcollecte/'+pointcollecte.currentValue[0]+'/rsd', {}, function(data) {
                vm.rsds.rows= _.forEach(data.result, function(value, key) {
                    value.date_collecte = vm.$filters.date.mysqlToDate(value.date_collecte)
                    value.nom_adresse_transporteur = value.transporteur_entree_nom      + ' | '+ value.transporteur_entree_adresse
                    value.nom_adresse_destination  = value.destination_regroupement_nom + ' | '+ value.destination_regroupement_adresse
                });
            })
        },
        downloadCsv: function() {
            axios({
                url: '/api/v1.0/client/pointcollecte/'+pointcollecte.currentValue[0]+'/rsd/export',
                method: 'GET',
                responseType: 'blob', // important
            }).then((response) => {
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', 'export_brute.xlsx'); //or any other extension
                document.body.appendChild(link);
                link.click();
            });
        }
    },
    mounted: function() {
        var vm = this
        pointcollecte.changedNotification = function() {vm.loadRsds()}
        vm.loadRsds()
    }
}
Utils.loader(currentComponentConfig)
currentComponent = Vue.createApp(currentComponentConfig)
Utils.filter(currentComponent)
currentComponent.use(Quasar, quasarConfig)
Object.keys(VueComponents).forEach(function(value, idx, tab) {
currentComponent.component(value, VueComponents[value])
})
Object.keys(VueBusinessComponents).forEach(function(value, idx, tab) {
currentComponent.component('business-'+value, VueBusinessComponents[value])
})
currentComponentVM = currentComponent.mount('#TOTORO')