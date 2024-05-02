currentComponentConfig = {
    el: '#TOTORO',
    data: function () {
        return {
          diagNouvelEven: false,
          diagNouvelleReception: false
        }
    },
    computed: {
    },
    watch: {
    },
    methods: {
      nouvelleReception: function() {
        var vm = this
        vm.diagNouvelEven = false
        vm.diagNouvelleReception = true
      }
    },
    mounted: function() {
      var vm = this
    }
  }
  Utils.loader(currentComponentConfig)
  currentComponent = Vue.createApp(currentComponentConfig)
  Utils.filter(currentComponent)
  currentComponent.use(Quasar, quasarConfig)
  Object.keys(VueComponents).forEach(function(value, idx, tab) {currentComponent.component(value, VueComponents[value])})
  Object.keys(VueBusinessComponents).forEach(function(value, idx, tab) {currentComponent.component('business-'+value, VueBusinessComponents[value])})
  currentComponentVM = currentComponent.mount('#TOTORO')


  var received = {
    "id": "clpsd9skl06ys9w35leb8f3uf",
    "readableId": "BSD-20231205-K3FTHHJTQ",
    "customId": null,
    "emittedAt": "2023-07-26T00:00:00.000Z",
    "emitter": {
      "type": "PRODUCER",
      "company": {
        "name": "Établissement de test",
        "siret": "00000000008252"
      },
      "workSite": {
        "name": "Site chantier",
        "address": "adresse du chantier",
        "postalCode": "92000",
        "city": "dans une ville",
        "infos": null
      }
    },
    "recipient": {

      "cap": "787CAP878",
      "processingOperation": "R13",
      "isTempStorage": false,
      "company": {
        "name": "TRIETHIC BY GAIA",
        "orgId": "52799365300031",
        "siret": "52799365300031",
        "address": "320 AV DE LA REPUBLIQUE 92000 NANTERRE",
        "country": "FR",
        "contact": "Majid EL IDRISSI",
        "phone": "0244760123",
        "mail": "informatique@triethic.fr",
        "vatNumber": null,
        "omiNumber": null
      }
    },
    "transporter": {
      "id": "clpsd9skm06yt9w35tc339xpt",
      "isExemptedOfReceipt": null,
      "receipt": "2011-009-T",
      "department": "92",
      "validityLimit": "2026-06-07T00:00:00.000Z",
      "numberPlate": null,
      "customInfo": null,
      "mode": "ROAD",
      "takenOverAt": null,
      "takenOverBy": null,
      "company": {
        "name": "TRIETHIC BY GAIA",
        "orgId": "52799365300031",
        "siret": "52799365300031",
        "address": "320 AV DE LA REPUBLIQUE 92000 NANTERRE",
        "country": "FR",
        "contact": "Majid EL IDRISSI",
        "phone": "0244760123",
        "mail": "informatique@triethic.fr",
        "vatNumber": null,
        "omiNumber": null
      }
    },
    "wasteDetails": {
      "name": "un dechet super dangereux",
      "code": "20 01 21*",
      "quantity": 0.11,
      "packagingInfos": [
        {
          "type": "AUTRE",
          "quantity": 1,
          "other": "en vrac"
        }
      ]
    },
    "createdAt": "2023-12-05T13:19:09.282Z",
    "updatedAt": "2023-12-05T13:22:52.736Z",
    "status": "SIGNED_BY_PRODUCER",
    "emittedBy": "Collecteur",
    "emittedByEcoOrganisme": false,
    "takenOverAt": null,
    "takenOverBy": null,
    "signedByTransporter": null,
    "wasteAcceptationStatus": null,
    "wasteRefusalReason": null,
    "receivedBy": null,
    "receivedAt": null,
    "signedAt": null,
    "quantityReceived": null,
    "processingOperationDone": null,
    "destinationOperationMode": null,
    "processingOperationDescription": null,
    "processedBy": null,
    "processedAt": null,
    "noTraceability": null
}

var expected = {
    "recipient": {
      "processingOperation": "R13",
      "isTempStorage": false,
      "company": {
        "siret": "52799365300031",
        "contact": "Franck BINOCHE",
        "phone": "0980774059",
        "mail": "franckbinoche@triethic.fr",
      }
    },
    "transporter": {
      "isExemptedOfReceipt": null,
      "receipt": "2011-009-T",
      "department": "92",
      "validityLimit": "2026-06-07T00:00:00.000Z",
      "mode": "ROAD",
      "company": {
        "siret": "52799365300031",
        "contact": "Franck BINOCHE",
        "phone": "0980774059",
        "mail": "franckbinoche@triethic.fr",
      }
    },
    "status": "SIGNED_BY_PRODUCER"
}
var tree = [ {
       id: 0,
       label: 'BSD',
       children: [
       ]
   }
]
var id = 0
var getNode = function(array, data, key) {
   var tmp = createTree(data[key])
   if (_.isObject(tmp) || _.isArray(tmp))
       array.push({id: ++id, label: key, key: key, children: tmp})
   else
       array.push({id: ++id, label: key+' : '+tmp, value: tmp, key: key, children: []})
}
var createTree = function (data) {
   var array = []
   if (_.isArray(data)) {
       data.forEach(function(key) {
           getNode(array, data, key)
       })
       return array
   } else if (_.isObject(data)) {
       array = []
       Object.keys(data).forEach(function(key) {
           getNode(array, data, key)
       })
       return array
   } else {
       return data
   }
}
var receivedRoot = createTree(received)
var expectedRoot = createTree(expected)
//<i class="fa-solid fa-triangle-exclamation"></i>

var checkNode = function(array, data, key) {
  var tmp = createTree(data[key])
  if (! _.isObject(tmp) && ! _.isArray(tmp))
      array.push({id: ++id, label: key          , value: null, key: key, children: tmp})
  else
      array.push({id: ++id, label: key+' : '+tmp, value: tmp , key: key, children: []})
}
var markWarning = function(expected, received) {
  if (_.isArray(expected.children)) {
    if (!_.isArray(received.children)) {
      received.icon = "fa-solid fa-triangle-exclamation"
    } else { //analyser les noeuds fils
      var toAdd = []
      expected.children.forEach(function(value) {
        var found = _.find(received.children, {key: value.key})
        if (found == undefined) {
          toAdd.push({icon:  "fa-solid fa-triangle-exclamation", id: ++id, label: 'Il manque l\'information suivante : '+value.key})
        } else {
          markWarning(value, found)
        }
      })
      ._merge(received.children, toAdd)
    }
  } else {
    if (received.value != expected.value) received.icon = "fa-solid fa-triangle-exclamation"
  }
}
//markWarning({children: expectedRoot}, {children: receivedRoot})
