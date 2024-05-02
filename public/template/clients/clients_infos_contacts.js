appVM.showPointcollecteSelector(true)
currentComponentConfig = {
  el: '#TOTORO',
  data: function () {
      return {
        contacts: [],
      }
  },
  computed: {
  },
  watch: {
  },
  methods: {
    loadContact: function() {
      var vm = this
      post('/api/v1.0/client/client/'+pointcollecte.currentClient+'/contact/list', {}, function(data) {
        vm.contacts = data.result
      })
    },
  },
  mounted: function() {
    var vm = this
    pointcollecte.changedNotification = function() {vm.loadContact()}
    vm.loadContact()
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