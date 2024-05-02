/*formCreator('pointcollecteZoom', [
  {
    type: 'line',
    model: 'pointcollecte.model.nom',
    label: 'Nom du point de collecte',
    required: true
  },
  {
    type: 'address',
    model: 'pointcollecte.model.adresse',
    label: 'Adresse',
    required: true,
    onaddressSelected: "pointcollecteAddressSelected",
  },
  {
    type: 'point',
    model: 'pointcollecte.model.coordonnees',
    label: 'Coordonnées GPS',
    onupdate: "checkPointcollecteChange()"
  },
  {
    type: 'phone',
    model: 'pointcollecte.model.telephone',
    label: 'Téléphone',
    onupdate: "checkPointcollecteChange()"
  },
  {
    type: 'input',
    model: 'pointcollecte.model.ascenseur',
    label: 'Ascenceur',
    raw: {maxlength: "4096", ':autogrow': "'true'"},
    onupdate: "checkPointcollecteChange()"
  },
  {
    type: 'input',
    model: 'pointcollecte.model.parking',
    label: 'Parking',
    raw: {maxlength: "4096", ':autogrow': "'true'"},
    onupdate: "checkPointcollecteChange()"
  },
  {
    type: 'input',
    model: 'pointcollecte.model.badge_acces',
    label: 'Badge accès',
    raw: {maxlength: "4096", ':autogrow': "'true'"},
    onupdate: "checkPointcollecteChange()"
  },
  {
    type: 'input',
    model: 'pointcollecte.model.hauteur',
    label: 'Hauteur',
    raw: {maxlength: "4096", ':autogrow': "'true'"},
    onupdate: "checkPointcollecteChange()"
  },
  {
    type: 'input',
    model: 'pointcollecte.model.batiment',
    label: 'Bâtiment',
    raw: {maxlength: "4096", ':autogrow': "'true'"},
    onupdate: "checkPointcollecteChange()"
  },
  {
    type: 'input',
    model: 'pointcollecte.model.code_acces',
    label: 'Code accès',
    raw: {maxlength: "4096", ':autogrow': "'true'"},
    onupdate: "checkPointcollecteChange()"
  },
  {
    type: 'input',
    model: 'pointcollecte.model.creneaux',
    label: 'Créneaux',
    raw: {maxlength: "4096", ':autogrow': "'true'"},
    onupdate: "checkPointcollecteChange()"
  },
  {
    type: 'checkbox',
    model: 'pointcollecte.model.producteur_dechet',
    label: 'Producteur du déchet',
    raw: {':left-label': true,':true-value': 1,':false-value': 0},
    onupdate: "checkPointcollecteChange()"
  },
  {
    type: 'input',
    model: 'pointcollecte.model.commentaire',
    label: 'Commentaire',
    raw: {maxlength: "4096", ':autogrow': "'true'"},
    onupdate: "checkPointcollecteChange()"
  }
], false, 'clientZoom')
*/
appVM.showPointcollecteSelector(true)
currentComponentConfig = {
  el: '#TOTORO',
  data: function () {
      return {
        pointcollectes: [],
      }
  },
  computed: {
  },
  watch: {
  },
  methods: {
    loadPointcollectes: function() {
      var vm = this
      post('/api/v1.0/client/pointcollecte/'+pointcollecte.currentValue[0]+'/view', {}, function(data) {
        vm.pointcollectes = data.result
      })
    },
  },
  mounted: function() {
    var vm = this
    pointcollecte.changedNotification = function() {vm.loadPointcollectes()}
    vm.loadPointcollectes()
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
