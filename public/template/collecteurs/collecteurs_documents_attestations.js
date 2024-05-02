var document_attestations_timer_checkDesc= 0;
currentComponentConfig = {
    el: '#TOTORO',
    data: function () {
      return {
        blockUi: false,
        blockUiMessage: '',
        notifyMail: '',
        generations: {
            available: [],
            rows: [],
            filter: '',
            pagination:  {
              sortBy: 'desc',
              descending: false,
              page: 1,
              rowsPerPage: 50
              // rowsNumber: xx if getting data from a server
            },
            columns: [
              {
                required: true,
                name: 'annee',
                label: 'Année',
                align: 'center',
                field: 'annee',
              },
              {
                required: true,
                name: 'text',
                label: 'Statut',
                align: 'center',
                field: 'text',
              }
            ]
        },
        pointcollectes: {
            available: [],
            rows: [],
            filter: '',
            pagination:  {
              sortBy: 'desc',
              descending: false,
              page: 1,
              rowsPerPage: 50
              // rowsNumber: xx if getting data from a server
            },
            columns: [
              {
                name: 'nom',
                required: true,
                label: 'Point de collecte',
                align: 'center',
                field: 'nom',
                sortable: true,
                class: 'tablecol2'
              },
              {
                required: true,
                name: 'annees',
                label: 'Années',
                align: 'center',
                field: 'annees',
                class: 'tablecol3'
              },
              {
                required: true,
                name: 'adresse',
                label: 'Adresse',
                align: 'center',
                field: 'adresse',
                class: 'tablecol4'
              }
            ]
        },
        attestations: {
            available: [],
            rows: [],
            filter: '',
            pagination:  {
              sortBy: 'desc',
              descending: false,
              page: 1,
              rowsPerPage: 50
              // rowsNumber: xx if getting data from a server
            },
            columns: [
              {
                required: true,
                name: 'annee',
                label: 'Année',
                align: 'center',
                field: 'annee',
              },
              {
                required: true,
                name: 'document',
                label: 'Document',
                align: 'center',
                field: 'document',
              }
            ]
        },
      }
    },
    computed: {
    },
    watch: {
    },
    methods: {
      loadPointcollectes: function() {
          var vm = this
          postV3({
            url:'/api/v1.0/integrateur/pointcollecte/attestations',
            success: function(data) {
              var annees = []
              vm.pointcollectes.rows = _.forEach(data.result, function(value) {
                sortedYears  = _.reduce(_.reverse(value.annees.split(',')), function(acc, value){acc.push(Number(value)); return acc}, [])
                annees       = _.unionBy(annees, _.reduce(sortedYears, function(acc, value){acc.push({annee: value, statut: 1, text: 'générés'});return acc}, []), 'annee')
                value.annees = _.join(sortedYears, ', ')
              })
              annees = _.sortBy(annees, 'annee')
              if (annees.length == 0)
                annees.push({annee: (new Date()).getFullYear() - 1, statut: 0, text: ''})
              for(var i = annees[0].annee, max = new Date().getFullYear(); i < max ; i++) {
                if (_.find(annees, {annee: i}) == undefined)
                  annees.push({annee: i, statut: 0, text: ''})
              }
              vm.generations.rows = _.reverse(_.sortBy(annees, 'annee'))
            }
          })
      },
      pointcollecteClick: function(props) {
        var vm = this
        var pointcollecte_id = props.row.id
        props.expand = !props.expand
        if (props.expand) {
          vm.$refs.pointcollectes.setExpanded([props.row.id])
          vm.loadPointcollecte(pointcollecte_id)
        }
      },
      loadPointcollecte: function(pointcollecte_id) {
        var vm = this
        postV3({
          url:'/api/v1.0/integrateur/pointcollecte/'+pointcollecte_id+'/attestations',
          success: function(data) {
            vm.attestations.rows = data.result
          }
        })
      },
      generationAttestation: function(props) {
        var vm = this
        Quasar.Loading.show({
          message: 'Génération du attestation'
        })
        var pointcollecte_id = props.row.pointcollecte_id
        postV3({
          url: '/api/v1.0/integrateur/attestation/'+pointcollecte_id+'/'+props.row.annee+'/generate',
          success: function(data) {
            if (data.result['failed'] != 0) {
              notify('negative', 'La génération a échoué. Nous vous invitions à contacter nos équipes.')
            }else{
              notify('positive', 'Génération réalisée.')
              vm.loadPointcollecte(pointcollecte_id)
            }
          },
          onFinally: function(){Quasar.Loading.hide()}
        })
      },
      generationAttestations: function(props) {
        var vm = this
        vm.blockUi = true
        const globalRegex = new RegExp(/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/);
        Quasar.Dialog.create({
          title: "Génération des attestations de « "+props.row.annee+" »",
          message: 'Envoi d\'un mail à la fin ?',
          prompt: {
            model: vm.notifyMail,
            isValid: val => val.length == 0 || globalRegex.test(val),
            type: 'text' // optional
          },
          cancel: true,
          persistent: true
        }).onOk(data => {
          vm.blockUi = true
          postV3({url: '/api/v1.0/integrateur/attestation/'+props.row.annee+'/generate', params: {email: data}, success: 'Génération en cours.'})
        })
      },
      checkStatus: function() {
        var vm = this
        postV3({
          url: '/api/v1.0/integrateur/attestation/0/status',
          success: function(data) {
            if (data.result.total_jobs == undefined) {
              if (vm.blockUi == true) {
                vm.loadPointcollectes()
                vm.blockUi = false
              }
            }
            else {
              vm.blockUi = true
              vm.blockUiMessage = 'Génération de attestations : '+(data.result.total_jobs-data.result.pending_jobs)+'/'+data.result.total_jobs+' (dont '+data.result.failed_jobs+' échec(s))'
            }
          }
        })
      },
      downloadAttestation: function(props) {
        var vm = this
        window.open('/api/v1.0/integrateur/attestation/'+props.row.pointcollecte_id+'/'+props.row.annee+'/download');
      },
    },
    mounted: function() {
      var vm = this
      vm.notifyMail = globalFrame.account.email
      vm.checkStatus()
      document_attestations_timer_checkDesc = window.setInterval(function() {vm.checkStatus()}, 5000);
      vm.loadPointcollectes()
    },
    unmounted: function() {
      window.clearInterval(document_attestations_timer_checkDesc)
    }

}
Utils.loader(currentComponentConfig)
currentComponent = Vue.createApp(currentComponentConfig)
Utils.filter(currentComponent)
currentComponent.use(Quasar, quasarConfig)
Object.keys(VueComponents).forEach(function(value, idx, tab) {currentComponent.component(value, VueComponents[value])})
Object.keys(VueBusinessComponents).forEach(function(value, idx, tab) {currentComponent.component('business-'+value, VueBusinessComponents[value])})
currentComponentVM = currentComponent.mount('#TOTORO')
