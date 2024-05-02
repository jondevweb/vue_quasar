appVM.showPointcollecteSelector(true)
currentComponentConfig = {
    el: '#TOTORO',
    data: function () {
        return {
            collectes: {
                available: [],
                rows: [],
                pagination: {
                    rowsPerPage: 0,
                    /*rowsNumber: rows.value.length*/
                },
                columns: [{
                    name: 'date',
                    required: true,
                    label: 'Date passage',
                    align: 'center',
                    field: 'date',
                    sortable: true,
                  },
                  {
                    name: 'nom',
                    required: true,
                    label: 'Point de collecte',
                    align: 'center',
                    field: 'nom',
                    sortable: true,
                  },
                  {
                    name: 'dechets',
                    required: true,
                    label: 'Déchets',
                    align: 'center',
                    field: 'dechets',
                    sortable: true,
                  },
                  {
                    name: 'poids',
                    required: true,
                    label: 'Masse totale (kg)',
                    align: 'center',
                    field: 'poids',
                    sortable: true,
                  }
                ]
              },
        }
    },
    computed: {
    },
    watch: {
        dayOfTheWeek: function(newVal, oldVal) {
            var vm = this
            vm.loadCollectes()
        }
    },
    methods: {
        loadCollectes: function() {
            var vm = this
            var params = {
                start: moment('2010-01-01').format('Y-MM-DD')+' 00:00:00',
                end:   moment()            .format('Y-MM-DD')+' 23:59:59',
            }
            if (pointcollecte.currentValue.length > 0)
                params.pointcollectes = pointcollecte.currentValue

            post('/api/v1.0/client/collecte/par_pointcollecte_avec_poids_et_dechet', params, function(data) {
                vm.collectes.rows= data.result
            })
        },
        statutToString: function(val) {
            switch (val) {
                case 0: return 'planifiée';break;
                case 1: return 'passage à vide';break;
                case 2: return 'réalisée';break;
                default: return 'N/A';break;
            }
        },
        downloadCsv: function() {
            axios({
                url: '/api/v1.0/client/collecte/export',
                method: 'GET',
                responseType: 'blob', // important
                params: {
                    pointcollectes: pointcollecte.currentValue,
                }
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
      pointcollecte.changedNotification = function() {vm.loadCollectes()}
      vm.loadCollectes()
    }
  }
  Utils.loader(currentComponentConfig)
  currentComponent = Vue.createApp(currentComponentConfig)
  currentComponent.config.compilerOptions.isCustomElement = function(tag) {
    return  ['style'].indexOf(tag) >= 0
  }
  Utils.filter(currentComponent)
  currentComponent.use(Quasar, quasarConfig)
  Object.keys(VueComponents).forEach(function(value, idx, tab) {
  currentComponent.component(value, VueComponents[value])
  })
  Object.keys(VueBusinessComponents).forEach(function(value, idx, tab) {
  currentComponent.component('business-'+value, VueBusinessComponents[value])
  })
  currentComponentVM = currentComponent.mount('#TOTORO')