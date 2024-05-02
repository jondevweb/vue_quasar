appVM.showPointcollecteSelector(true)
var dayOfTheWeek = appGlobal['clients/collectes_calendrier'] != undefined ? moment(appGlobal['clients/collectes_calendrier'], 'Y/MM/DD') :  moment()
currentComponentConfig = {
    el: '#TOTORO',
    data: function () {
        return {
            dayOfTheWeek: dayOfTheWeek.format('Y/MM/DD'),
            calendar: {
              events: [],
              month: dayOfTheWeek.format('MM'),
              year: dayOfTheWeek.format('Y'),
            },
            passageDays: {
              rows:[]
            },
            futurPassageDays: {
              rows:[]
            },
            collectes: {
                available: [],
                rows: [],
                columns: [{
                    name: 'date',
                    required: true,
                    label: 'Date passage',
                    align: 'center',
                    field: 'date',
                  },
                  {
                    name: 'nom',
                    required: true,
                    label: 'Point de collecte',
                    align: 'center',
                    field: 'nom',
                  },
                  {
                    name: 'statut',
                    required: true,
                    label: 'Statut',
                    align: 'center',
                    field: 'statut',
                  },
                  {
                    name: 'dechets',
                    required: true,
                    label: 'Déchets',
                    align: 'center',
                    field: 'dechets',
                  },
                  {
                    name: 'poids',
                    required: true,
                    label: 'Masse totale (kg)',
                    align: 'center',
                    field: 'poids',
                  }
                ]
              },
        }
    },
    computed: {
        calendarTitle: function() {
            var vm = this
            if (vm.dayOfTheWeek == null)
                return 'Sélectionnez un jour'
            return moment(vm.dayOfTheWeek.replaceAll('/', '-')).format('DD MMM')
        },
    },
    watch: {
        dayOfTheWeek: function(newVal, oldVal) {
            var vm = this
            if (newVal == null) return
            vm.loadCollectes()
        }
    },
    methods: {
        requestCalendarParams: function() {
            var vm = this
            return {
                pointcollectes: pointcollecte.currentValue,
                start: vm.calendar.year+'-'+vm.calendar.month+'-01 00:00:00',
                end: moment([vm.calendar.year, vm.calendar.month-1]).add(1, 'months').format('Y-MM-DD')+' 00:00:00'
            }
        },
        loadCalendarData: function(view) {
          var vm = this
          vm.calendar.year  = view.year
          vm.calendar.month = view.month
          post('/api/v1.0/client/passage/date_par_pointcollecte', vm.requestCalendarParams(), function(data) {
            var events = []
            _.forEach(data.result, function(value) {
              events.push(value.date_debut.replace(/ [0-9]{2}:[0-9]{2}:[0-9]{2}/g, '').replace(/-/g, '/'))
            })
            vm.calendar.events = events
            vm.loadFuturPassageDay()
          })
        },
        loadCollectes: function() {
            var vm = this
            var params = {
                start: moment(vm.dayOfTheWeek.replaceAll('/', '-')).format('Y-MM-DD')+' 00:00:00',
                end:   moment(vm.dayOfTheWeek.replaceAll('/', '-')).format('Y-MM-DD')+' 23:59:59',
            }
            if (pointcollecte.currentValue.length > 0)
                params.pointcollectes = pointcollecte.currentValue

            post('/api/v1.0/client/collecte/par_pointcollecte_et_passage', params, function(data) {
                var today = moment().format('YMMDD')
                vm.collectes.rows = _.forEach(data.result, function(value) {
                  if (value.dechets != null) {
                    value.dechets = _.reduce(value.dechets.split(','), function(acc, val){
                      if (val.match(/.*#100/)) {
                        val = val.replace(/#100/, '')
                        acc.push(val)
                      }
                      return acc}
                    , []).join(', ')
                    if (value.dechets.length == 0)  {
                      value.dechets = null
                      value.poids = null
                    }
                  }
                  value.isPast = value.date.split(' ')[0].replace(/-/g, '') < today
                });
            })
        },
        loadLastPassageDay: function() {
          var vm = this
          var params = {
            limit: 10,
            pointcollectes: pointcollecte.currentValue,
            end: moment().format('Y-MM-DD')+' 00:00:00'
          }
          post('/api/v1.0/client/pointcollecte/passage/liste_derniers_jours', params, function(data) {
            vm.passageDays.rows = _.forEach(data.result, function(value) {
              value.formated       = moment(value.date, 'Y-MM-DD').format('DD/MM/Y')
              value.calendarFormat = value.date.replaceAll('-', '/')
            })
          })
        },
        loadFuturPassageDay: function() {
          var vm = this
          var params = {
            limit: 100,
            pointcollectes: pointcollecte.currentValue,
          }
          post('/api/v1.0/client/pointcollecte/passage/liste_futurs_jours', params, function(data) {
            vm.futurPassageDays.rows = _.forEach(_.take(data.result, 10), function(value) {
              value.formated       = moment(value.date, 'Y-MM-DD').format('DD/MM/Y')
              value.calendarFormat = value.date.replaceAll('-', '/')
            })
            var nextMonth = moment(vm.calendar.year+'-'+vm.calendar.month+'.1', 'YYYY-MM-DD').add(1, 'months')
            vm.calendar.events = _.concat(JSON.parse(JSON.stringify(vm.calendar.events)), _.reduce(data.result, function(acc, value) {
              var currentDate = moment(value.date, 'YYYY-MM-DD HH:mm:ss')
              if (currentDate.isBefore(nextMonth))
                acc.push(currentDate.format('YYYY/MM/DD'))
              return acc
            }, []))
          })
        },
        statutToString: function(val, isPast) {
            switch (val) {
                case 0: return isPast ? 'traitement en cours' : 'planifiée';break;
                case 1: return 'passage à vide';break;
                case 2: return 'réalisée';break;
                default: return 'N/A';break;
            }
        },
        downloadCsv: function() {
            axios({
                url: '/api/v1.0/client/collecte/futur/export',
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
        },
        jumpToDocs: function() {
          var vm = this
          appGlobal['clients/collectes_calendrier'] = vm.dayOfTheWeek
          switchPage('clients/documents_collecte')
        }
    },
    mounted: function() {
      var vm = this
      delete appGlobal['clients/collectes_calendrier']
      pointcollecte.changedNotification = function() {
        vm.loadCollectes()
        vm.loadCalendarData({year: vm.calendar.year, month: vm.calendar.month})
        vm.loadLastPassageDay()
      }
      pointcollecte.changedNotification()
    }
  }
  Utils.loader(currentComponentConfig)
  currentComponent = Vue.createApp(currentComponentConfig)
  Utils.filter(currentComponent)
  currentComponent.use(Quasar, quasarConfig)
  Object.keys(VueComponents).forEach(function(value, idx, tab) {currentComponent.component(value, VueComponents[value])})
  Object.keys(VueBusinessComponents).forEach(function(value, idx, tab) {currentComponent.component('business-'+value, VueBusinessComponents[value])})
  currentComponentVM = currentComponent.mount('#TOTORO')
