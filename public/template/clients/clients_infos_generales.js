appVM.showPointcollecteSelector(true)
currentComponentConfig = {
    el: '#TOTORO',
    data: function () {
        return {
          client: {
            model: {
              entreprise: {}
            },
          },
          contact_juridique: {
            model: {
            }
          },
          gestionnaire: {model: {entreprise: {}},},
          contact_gestionnaire: {model: {},},
        }
    },
    computed: {
    },
    watch: {
    },
    methods: {
      loadClient: function() {
        var vm = this
        post('/api/v1.0/client/client/'+pointcollecte.currentClient, {}, function(data) {
          vm.client.model = data.result
          if (vm.client.model.gestionnaire_id)
            vm.loadGestionnaire()
          else {
            vm.gestionnaire = {model: {entreprise: {}},}
            vm.contact_gestionnaire = {model: {},}
          }
        })
      },
      loadContactJuridique: function() {
        var vm = this
        post('/api/v1.0/client/client/'+pointcollecte.currentClient+'/contact/juridique', {}, function(data) {
          vm.contact_juridique.model = data.result
        })
      },
      loadGestionnaire: function() {
        var vm = this
        post('/api/v1.0/client/client/'+pointcollecte.currentClient+'/gestionnaire_et_contact', {}, function(data) {
          vm.gestionnaire.model         = data.result.gestionnaire
          vm.contact_gestionnaire.model = data.result.contact_gestionnaire
        })
      },
    },
    mounted: function() {
      var vm = this
      pointcollecte.changedNotification = function() {vm.loadClient();vm.loadContactJuridique()}
      vm.loadClient()
      vm.loadContactJuridique()
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