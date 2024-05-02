appVM.showPointcollecteSelector(true)
currentComponentConfig = {
    el: '#TOTORO',
    data: function () {
        return {
            attestations: {
                selected: [],
                available: [],
                pagination: {
                    rowsPerPage: 0,
                },
                rows: [],
                columns: [{
                    name: 'annee',
                    required: true,
                    label: 'Année',
                    align: 'center',
                    field: 'annee',
                    sortable: true,
                  },
                ]
              },
        }
    },
    computed: {
    },
    watch: {
    },
    methods: {
        loadAttestations: function() {
            var vm = this
            post('/api/v1.0/client/attestation', {pointcollecte_ids: pointcollecte.currentValue}, function(data) {
                vm.attestations.rows = data.result
            })
        },
        documentDownload: function(attestation_id) {
          window.open('/api/v1.0/client/attestation/'+attestation_id)
        },
        downloadSelected: function() {
            var vm = this
            params = new URLSearchParams()

            params.set('attestation_ids', JSON.stringify(_.reduce(vm.attestations.selected, function(acc, value){acc.push(value.id);return acc}, [])))
            window.open('/api/v1.0/client/attestation/documents?'+params.toString());
        }
    },
    mounted: function() {
      var vm = this
      pointcollecte.changedNotification = function() {vm.loadAttestations()}
      vm.loadAttestations()
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