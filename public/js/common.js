const IGN_KEY = 'ydapdduh7mkccnhdacv77jzu'

String.prototype.split2 = function (string) {
  var result = this.split(string)
  if (result.length == 1)
    if (result[0] == '')
      return [];
  return result
};
String.prototype.simplify = function () {
  return this.trim().replace(/\s+/g, ' ')
};
var common = {
  working: false
}
chainData={
  next: function() {
    if (chainData.functions.length  == 0) return;
    chainData.functions[0](function(){chainData.functions.splice(0, 1);chainData.next()});
  },
  functions: []
}
/* exemple
chain(function(next){setTimeout(function(){console.debug('COUCOU1'); next()}, 3000)})
chain(function(next){setTimeout(function(){console.debug('COUCOU2'); next()}, 1000)})
chain(function(next){setTimeout(function(){console.debug('COUCOU3'); next()}, 500)})
chain(function(next){setTimeout(function(){console.debug('COUCOU4'); next()}, 1000)})
chain(function(next){setTimeout(function(){console.debug('COUCOU5'); next()}, 10)})
chain(function(next){setTimeout(function(){console.debug('COUCOU6'); next()})})
*/
chain = function(func) {
  if (typeof func != 'function') return
  chainData.functions.push(func);
  if (chainData.functions.length == 1)
    chainData.next()
}

function addEvent(element, eventName, callback) {//https://stackoverflow.com/questions/16089421/how-do-i-detect-keypresses-in-javascript
  if (element.addEventListener) {
      element.addEventListener(eventName, callback, false);
  } else if (element.attachEvent) {
      element.attachEvent("on" + eventName, callback);
  } else {
      element["on" + eventName] = callback;
  }
}
function notify(type, text, params) {
  var defaultParams = {
    message: text,
    timeout: ['negative', 'warning'].indexOf(type) >= 0 ? 0 : 1500,
    progress: true,
    html: true,
    position: 'top-right',
    type: type
  };
  if (['negative', 'warning'].indexOf(type) >= 0)
    defaultParams.actions= [{ label: 'Compris', color: 'white' }]
  else
    defaultParams.timeout = Math.max(Math.floor((text.length/15)*1000), 1500)

  if (params != null && params != undefined)
    _.defaultsDeep(params, defaultParams)
  else
    params = defaultParams;
  Quasar.Notify.create(params)
}
function waitingScreen(show) {
  if (show === true) {
    Quasar.Loading.show({
      spinner: Quasar.QSpinnerGears,
      delay: 400
    })
  }else{
    Quasar.Loading.hide()
  }
}
function post2(params) {
  post(params.url, params.params, params.success, params.error, params.waitingScreenToShow, params.onFinally);
}
function postV3Download(params) {
  try{
    if (params.waitingScreenToShow == true)
      waitingScreen(true);
    axios.post(params.url, params.params ? params.params : {}, {responseType: 'blob'})
    .then(function (response) {
      download(response.data, params.filename)
    }).catch(function (errorObj) {
      if      (_.isFunction(params.error))            params.error(errorObj.response ? errorObj.response : errorObj)
      else if (_.isString(params.error)  == 'string') notify('warning', params.error);
      else {
        if (errorObj.response ? errorObj.response.status == 429 : errorObj) notify('negative', 'Trop de demandes en peu de temps; attendez quelques secondes et recommencez.');
        else notify('negative', 'Une erreur s\'est produite.<BR>Nous vous invitons essayer à nouveau et le cas échéant nous contacter.');
        console.debug(errorObj)
      }
    }).finally(function() {
      if     (_.isFunction(params.onFinally))      params.onFinally()
      else if(params.waitingScreenToShow === true) waitingScreen(false);
    })
  }catch (error) {
    if     (_.isFunction(params.onFinally))      params.onFinally()
    else if(params.waitingScreenToShow === true) waitingScreen(false);
  }
}
/*
  url: self explaining
  params: axios params
  success: on success ; either a string or a function
  error: on success ; either a string or a function
  waitingScreenToShow: self explaining
  onFinally: method to run every time
  next: if success then call postV3 with it as params
*/
function postV3(params) {
  try{
    if (params.waitingScreenToShow == true)
      waitingScreen(true);
    axios.post(params.url, params.params ? params.params : {})
    .then(function (response) {
      if     (_.isFunction(params.success)) params.success(response.data)
      else if(_.isString(params.success))   notify('positive', params.success);
      else if(_.isObject(params.next))      postV3(params.next)
      else notify('positive', 'Opération réalisée.');
    }).catch(function (errorObj) {
      if      (_.isFunction(params.error))            params.error(errorObj.response ? errorObj.response : errorObj)
      else if (_.isString(params.error)  == 'string') notify('warning', params.error);
      else {
        if (errorObj.response ? errorObj.response.status == 429 : errorObj) notify('negative', 'Trop de demandes en peu de temps; attendez quelques secondes et recommencez.');
        else notify('negative', 'Une erreur s\'est produite.<BR>Nous vous invitons essayer à nouveau et le cas échéant nous contacter.');
        console.debug(errorObj)
      }
    }).finally(function() {
      if     (_.isFunction(params.onFinally))      params.onFinally()
      else if(params.waitingScreenToShow === true) waitingScreen(false);
    })
  }catch (error) {
    if     (_.isFunction(params.onFinally))      params.onFinally()
    else if(params.waitingScreenToShow === true) waitingScreen(false);
  }
}
function directPost(url, params, success, error, waitingScreenToShow, onFinally) {
  try{
    common.working = true
    if (waitingScreenToShow === true)
      waitingScreen(true);
    axios.post(url, params)
    .then(function (response) {
      if (success != null && success != undefined && typeof(success) == 'function' )
        success(response.data)
      else if (typeof(success)  == 'string')
        notify('positive', success);
      else
        notify('positive', 'Opération réalisée.');
      if (typeof(onFinally)  == 'function') onFinally()
      common.working = false
    })
    .catch(function (errorObj) {
      if (error != null && error != undefined && typeof(error) == 'function' )
        error(errorObj.response ? errorObj.response : errorObj)
      else if (typeof(error)  == 'string')
        notify('warning', error);
      else {
        if (errorObj.response ? errorObj.response.status == 429 : errorObj)
          notify('negative', 'Trop de demandes en peu de temps; attendez quelques secondes et recommencez.');
        else
          notify('negative', 'Une erreur s\'est produite.<BR>Nous vous invitons essayer à nouveau et le cas échéant nous contacter.');
        //if (typeof(onFinally)  == 'function') onFinally()
        console.debug(errorObj)
      }
      common.working = false
    })
    .finally(function() {
      if (typeof(onFinally)  == 'function') onFinally()
      else waitingScreen(false);
    })
  }catch (error) {
    if (typeof(onFinally)  == 'function') onFinally()
    else waitingScreen(false);
  }
}
function post(url, params, success, error, waitingScreenToShow, onFinally) {
  chain(function(next){directPost(url, params, success, error, waitingScreenToShow, function(){next();waitingScreen(false);if (typeof(onFinally)  == 'function') onFinally()})});
}
function get(url, params, success, error, waitingScreenToShow) {
  if (waitingScreenToShow === true)
    waitingScreen(true);
  axios.get(url, {params: params})
  .then(function (response) {
    if (success != null && success != undefined && typeof(success) == 'function' )
      success(response.data)
    else if (typeof(success)  == 'string')
      notify('positive', success);
    else
      notify('positive', 'Opération réalisée.');
  })
  .catch(function (errorObj) {
    if (error != null && error != undefined && typeof(error) == 'function' )
      error(errorObj.response)
    else if (typeof(error)  == 'string')
      notify('warning', error);
    else {
      notify('error', 'Une erreur s\'est produite.<BR>Nous vous invitons essayer à nouveau et le cas échéant nous contacter.');
      console.debug(errorObj)
    }
  })
  .finally(function() {
    waitingScreen(false);
  })
}
function postWithWaitingScreen(url, params, success, error, onFinally) {
  post(url, params, success, error, true, onFinally);
}
function postWithoutWaitingScreen(url, params, success, error, onFinally) {
  post(url, params, success, error, false, onFinally);
}
function loadJSScript(src, onerror) {
  var oScript = document.createElement("script");
  var oHead = document.head || document.getElementsByTagName("head")[0];
  oScript.type = "text\/javascript";
  if (onerror != undefined && onerror != null)
    oScript.onerror = onerror;
  else
    oScript.onerror = function() {notify ('negative', 'Un problème de connexion s\'est produit ; veuillez re-charger la page, ou contacter nos équipes.')};
  oHead.appendChild(oScript);
  oScript.src = src;
}
function loadComponent(anchor, file) {
  $( anchor ).load( file+'.html', undefined, function(responseText, textStatus, jqXHR) {
    if (textStatus == 'success' || textStatus == 'notmodified')
      loadJSScript(file+'.js');
  });
}
function getCookies() { //lodash required
  var retour = Cookies.get('triethic')
  if (retour == undefined) return {}
  return {triethic: JSON.parse(atob(retour))}
}
function setCookies(data) { //lodash required
  if (typeof data != 'object' || data == null) {
    console.warn('invalid object given as cookie')
    return
  }
  Cookies.set('triethic', btoa(JSON.stringify(data)), { expires: 365, secure: true, sameSite: 'Strict' })
}
// see https://lodash.com/docs/#merge
function updateCookies(data) { //lodash required
  if (typeof data != 'object' || data == null) {
    console.warn('invalid object given as cookie')
    return
  }
  Cookies.set('triethic', btoa(JSON.stringify(_.merge(getCookies().triethic, data))), { expires: 365, secure: true, sameSite: 'Strict' })
}

function JoursFeries (an){ //vient de https://codes-sources.commentcamarche.net/source/16245-calcul-des-jours-feries
  var JourAn = new Date(an, "00", "01")
  var FeteTravail = new Date(an, "04", "01")
  var Victoire1945 = new Date(an, "04", "08")
  var FeteNationale = new Date(an,"06", "14")
  var Assomption = new Date(an, "07", "15")
  var Toussaint = new Date(an, "10", "01")
  var Armistice = new Date(an, "10", "11")
  var Noel = new Date(an, "11", "25")
  var SaintEtienne = new Date(an, "11", "26")

  var G = an%19
  var C = Math.floor(an/100)
  var H = (C - Math.floor(C/4) - Math.floor((8*C+13)/25) + 19*G + 15)%30
  var I = H - Math.floor(H/28)*(1 - Math.floor(H/28)*Math.floor(29/(H + 1))*Math.floor((21 - G)/11))
  var J = (an*1 + Math.floor(an/4) + I + 2 - C + Math.floor(C/4))%7
  var L = I - J
  var MoisPaques = 3 + Math.floor((L + 40)/44)
  var JourPaques = L + 28 - 31*Math.floor(MoisPaques/4)
  var Paques = new Date(an, MoisPaques-1, JourPaques)
  var VendrediSaint = new Date(an, MoisPaques-1, JourPaques-2)
  var LundiPaques = new Date(an, MoisPaques-1, JourPaques+1)
  var Ascension = new Date(an, MoisPaques-1, JourPaques+39)
  var Pentecote = new Date(an, MoisPaques-1, JourPaques+49)
  var LundiPentecote = new Date(an, MoisPaques-1, JourPaques+50)

  return new Array({text: 'Jour de l\'an', date: JourAn}
                 , {text: 'Vendredi Saint (Alsace)', date: VendrediSaint}
                 , {text: 'Pâques (Alsace)', date: Paques}
                 , {text: 'Lundi de Pâques', date: LundiPaques}
                 , {text: 'Fête du travail', date: FeteTravail}
                 , {text: 'Victoire des Alliés', date: Victoire1945}
                 , {text: 'Ascension', date: Ascension}
                 , {text: 'Pentecôte (Alsace)', date: Pentecote}
                 , {text: 'Lundi de Pencôte', date: LundiPentecote}
                 , {text: 'Fête Nationale', date: FeteNationale}
                 , {text: 'Assomption', date: Assomption}
                 , {text: 'Toussaint', date: Toussaint}
                 , {text: 'Armistice', date: Armistice }
                 , {text: 'Noël', date: Noel}
                 , {text: 'Saint Étienne (Alsace)', date: SaintEtienne}
  )
}
if (moment != undefined) {moment.locale('fr');moment.tz.setDefault('Europe/Paris')}

function enterFullScreen() {//https://stackoverflow.com/questions/16371504/html5-fullscreen-api-toggle-with-javascript
  var el = document.documentElement,
      rfs = el.requestFullScreen
        || el.webkitRequestFullScreen
        || el.mozRequestFullScreen
        || el.msRequestFullscreen;

  rfs.call(el);
}
function exitFullScreen() {
  if (document.exitFullscreen != undefined)
    document.exitFullscreen()
  else
    document.webkitExitFullscreen()
}
function isWebPageVisible() {
  if (typeof document.hidden !== "undefined") { // Opera 12.10 and Firefox 18 and later support
    return !document.hidden
  } else if (typeof document.msHidden !== "undefined") {
    return !document.msHidden
  } else if (typeof document.webkitHidden !== "undefined") {
    return !document.webkitHidden
  }
  return false
}
Utils = {
    filter:function(vmApp) {
      vmApp.config.globalProperties.$filters = {
        date: {
          mysqlToDate: function(value) {
            return moment(value).format('DD/MM/Y')
          }
        }
      }
    },
  loader:function(vmConfig) {
    vmConfig.methods.siretValidator = function(val) {
      return Utils.validators.siret(val) || 'Le SIRET n\'est pas valide'
    }
    vmConfig.methods.copyToClipboard = function(text) {
      Quasar.copyToClipboard(text)
      .then(() => {notify('positive', 'Données placées dans le presse-papier.')})
      .catch(() => {notify('negative', 'Problème d\'accès au presse-papier.')})
    },
    vmConfig.methods.phoneValidator = function(val) {
      return libphonenumber.isValidNumber(val, 'FR') || 'Le numéro n\'est pas valide'
    }
    vmConfig.methods.emailValidator = function(val) {
      return Utils.validators.email(val) || 'Le courriel n\'est pas valide'
    }
    vmConfig.methods.phoneFormat = function(val) {
      return libphonenumber.parsePhoneNumber(val, 'FR').formatInternational()
    }
    vmConfig.methods.convertDate = function(val, formatFrom, formatTo) {
      return moment(val, formatFrom).format(formatTo)
    }
    vmConfig.methods.diff  = Utils.misc.diff
    vmConfig.methods.minus = Utils.misc.minus
    vmConfig.methods.oppositeColor      = Utils.misc.oppositeColor
    vmConfig.methods.downloadGraphAsPng = Utils.misc.downloadGraphAsPng
  },
  misc: {
    // a = string of the new model
    // b = string of the old model
    // return object from a that are "new"
    diff: function(a, b, preprocess) {
      if (typeof preprocess != 'function')
        preprocess = function(a){return a}
      var params = a
      var old    = b
      if (typeof params == 'string') params = preprocess(JSON.parse(a))
      if (typeof old    == 'string') old    = preprocess(JSON.parse(b))
      Object.keys(old).forEach(function(val) {
        if (old[val] == params[val])
          delete params[val]
      })
      return params
    },
    // a = new model
    // b = old model
    // return object from a that are "new"
    minus: function(newNode, oldNode, idMap) {
      var copyNewNode = _.cloneDeep(newNode)
      var minusWrapped = function(newNode, oldNode, idMap) {
        if (_.isEqual(newNode, oldNode)) return true
        if (typeof newNode != typeof oldNode) return false
        if (newNode instanceof Array) {
            var tmp = 0
            var equal = true
            for(var i = oldNode.length-1 ; i >= 0 ; i--) {
                if (typeof oldNode[i] != 'object') {
                    if ((tmp = newNode.indexOf(oldNode[i])) != -1) {
                        equal = false
                        newNode.splice(tmp, 1)
                    }
                }else{
                    for(var j =  newNode.length -1 ; j >= 0 ; j--) {
                        if (minusWrapped(newNode[j], oldNode[i], idMap)) {
                            newNode.splice(j, 1)
                            equal = false
                        }
                    }
                }
            }
            //if (newNode.length == 0 && oldNode.length != 0) return true //c.f. plus bas
            if(equal) return newNode.length == 0
            if(equal == false) return false
        }
        if (newNode instanceof Object) {
          if (newNode.constructor.name != oldNode.constructor.name)
            return false
          if (!newNode.constructor.name.startsWith('XXObj_triethic_'))
            return false
          var id = idMap[newNode.constructor.name]
          if (id == undefined)
            return false
          if (newNode[id] != oldNode[id])
            return false
          var keys = Object.keys(oldNode)
          var equal = true
          for(value in keys) {
              if (_.isEqual(newNode[keys[value]], oldNode[keys[value]])) delete newNode[keys[value]]
              else {
                  if (keys[value] in newNode) {
                      if (minusWrapped(newNode[keys[value]], oldNode[keys[value]], idMap)) {
                          delete newNode[keys[value]]
                          equal = false
                      }
                  }
              }
          }
          //if (Object.keys(newNode).length == 0 && Object.keys(oldNode).length != 0) return true
          //Si rien de supprimé et si objet non vide alors on retourne faux (on ne supprime pas)
          //                    et si objet est vide alors on retourne faux (on ne supprime pas)
          //Si on a retiré des choses et que l'objet est vide alors on retourne vrai (on supprime)
          //                          et que l'objet n'est pas vide alors on retourne faux (on ne supprime pas)
          if(equal) return Object.keys(newNode).length == 0
          if(equal == false) return false
        }
        if (typeof newNode == 'function')
            return newNode.toString() == oldNode.toString()
        return false
      }
      minusWrapped(copyNewNode, oldNode, idMap ? idMap : {})
      return copyNewNode
    },
    displayMap: function (div, latitude, longitude) {
      return Gp.Map.load(
        div, // html div
        {
            // Geoportal access key obtained here : http://professionnels.ign.fr/ign/contrats
            //apiKey: 'essentiels', //IGN_KEY,
            // map center
            center : {
                //location : "73 avenue de Paris, Saint-Mandé"
                x : Number(longitude),
                y : Number(latitude),
                projection : "CRS:84",
            },
            // map zoom level
            zoom : 18,
            // layers to display
            layersOptions : {
              "GEOGRAPHICALGRIDSYSTEMS.PLANIGNV2" : {format : "WMS",},
              "ORTHOIMAGERY.ORTHOPHOTOS" : {opacity : 0.4, format : "WMS",},
            },
            // additional tools to display on the map
            controlsOptions : {
                "search" : {
                    maximised : true
                }

            },
            configUrl : '/geoservices.ign/autoconf-https.js',
            // markers to put in the map
            markersOptions : [{}]
        }
      );
    },
    look4: function(adresse, success, error, finaly, searchParams) {
      var defaultParams = {
        'text'  : adresse,
        'poiType': 'administratif',
        'ter': 'DOMTOM,METROPOLE',
        'type': 'StreetAddress',
        'maximumResponses': 10
      }
      if (searchParams != undefined)
        _.merge(defaultParams, searchParams)

      var params = new URLSearchParams(defaultParams);
      axios.get('https://data.geopf.fr/geocodage/completion/?'+params.toString())
      .then(function (response) {
        if (success != null && success != undefined && typeof(success) == 'function' )
          success(response.data)
      })
      .catch(function (err) {
        if (error != null && error != undefined && typeof(error) == 'function' ) {
          error(err)
          return
        }
        notify('error', 'Une erreur s\'est produite.<BR>Nous vous invitons essayer à nouveau et le cas échéant nous contacter.');
        console.debug(err)
      })
      .then(function () {
        if (finaly != null && finaly != undefined && typeof(finaly) == 'function' )
        finaly()
      })
    },
    oppositeColor: function(color) {
      if (color == '' || color == null || color == undefined) return ''
      return Quasar.colors.brightness(color) < 128 ? Quasar.colors.lighten(color, 100) : Quasar.colors.lighten(color, -100)
    },
    downloadGraphAsPng: function(object, filename) { //object must be a chartjs one
      var link = document.createElement('a');
      link.href = object.toBase64Image();
      link.download = filename;
      link.click();
    }
  },
  validators: {
    email: function(mail) {
      return validator.isEmail(mail)
    },
    siret: function(siret) {
      siret = siret.replace(/[^0-9]/g, '')
      if (siret.length != 14)
        return false;
      var sum = 0;
      var tmp;
      if (siret.indexOf("356000000") == 0) {// postal exception
        for (var i = 0, size = siret.length; i < size; i++) {
          tmp = siret.charCodeAt(i) - 48;
          if (tmp < 0 || tmp > 9)
            return false;
          sum += tmp;
        }

        return sum % 5 == 0;
      }
      for (var i = 0, size = siret.length; i < size; i++) {
        tmp = siret.charCodeAt(i) - 48;
        if (tmp < 0 || tmp > 9)
          return false;
        if (i % 2 == 0) {
          tmp *= 2;
          if (tmp > 9)
            tmp -= 9;
        }
        sum += tmp;
      }
      return sum % 10 == 0;
    }
  }
}

function formCreator(anchor, data, removeId, parentTemplateAnchor) {
  var isTemplate = false
  node = $(anchor)

  if (parentTemplateAnchor != undefined) {
    isTemplate = true
    node = document.getElementById(parentTemplateAnchor)
    if (node == null)  {
      notify('negative', 'Une erreur s\'est produite.<BR>Nous vous invitons essayer à nouveau et le cas échéant nous contacter.');
      return
    }
  }else if (node.length == 0) {
    node = document.getElementById(anchor)
    if (node == null)  {
      notify('negative', 'Une erreur s\'est produite.<BR>Nous vous invitons essayer à nouveau et le cas échéant nous contacter.');
      return
    }
    isTemplate = node.tagName == 'TEMPLATE'
  }
  var buffer = ''
  data.forEach(elt => {
    if (elt.type == 'input') {
      buffer += `<q-input filled outlined class='form-input' :unmasked-value='true' debounce='750' v-model='`+elt.model+`' label='`+elt.label+`'`
      if (elt.onupdate != undefined)  buffer += ` @update:model-value='`+elt.onupdate+`' `
      if (elt.rules    != undefined)  buffer += ' :rules="'    +elt.rules    +'"'
      else buffer += ' :rules="[ val => true || \'\']"'
      if (elt.mask     != undefined)  buffer += ' mask="'      +elt.mask     +'"'
      if (elt.required === true)  buffer += ' required '
      if (elt.fillmask != undefined)  buffer += ' fill-mask="' +elt.fillmask +'"'
      buffer += (elt.readonly === true ? ' readonly ': '')
      + _.reduce(elt.raw !== undefined? elt.raw : elt.raw, function(acc, value, key){
        return acc +' '+key+'='+value+' '
      }, '')

      + '>'
      if (elt.copyToClipboard === true)
        buffer += `<template v-slot:append>
                      <q-icon name="far fa-clipboard" @click='copyToClipboard(`+elt.model+`)'></q-icon>
                  </template>`
      buffer += '</q-input>'
      return
    }
    if (elt.type == 'checkbox') {
      buffer += `<q-checkbox v-model='`+elt.model+`' label='`+elt.label+`'`
      if (elt.onupdate != undefined)  buffer += ` @update:model-value='`+elt.onupdate+`' `
      buffer += (elt.readonly === true ? ' disable ': '')
      + _.reduce(elt.raw !== undefined? elt.raw : elt.raw, function(acc, value, key){
        return acc +' '+key+'='+value+' '
      }, '')
      + '></q-checkbox>'
      return
    }
    if (elt.type == 'date') {
      buffer += `
      <q-input filled readonly outlined class='form-input' debounce='750' v-model='`+elt.model+`' label='`+elt.label+`'`+(elt.readonly === true ? 'readonly': '')
      if (elt.onupdate != undefined)  buffer += ` @update:model-value='`+elt.onupdate+`' `
      if (elt.mask     != undefined)  buffer += ' mask="'      +elt.mask     +'" '
      if (elt.required === true)  buffer += ' required '
      buffer += `>
        <template v-slot:append>
          <q-icon name="event" class="cursor-pointer">
            <q-popup-proxy ref="qDateProxy">
              <q-date v-model="`+elt.date.model+`" `
              if (elt.date.mask != undefined) buffer += ' mask="' + elt.date.mask +'" '
              else
                buffer += ' mask="YYYY-MM-DD" '

      buffer+= `>
                <div class="row items-center justify-end">
                  <q-btn v-close-popup label="Fermer"></q-btn>
                </div>
              </q-date>
            </q-popup-proxy>
          </q-icon>
        </template>
      </q-input>`
      return
    }
    if (elt.type == 'phone') {
      buffer += '<input-phone '
                +(elt.readonly  === true      ? ' :readonly="true"'                    : '')
                +(elt.required      === true      ? ' :required="true"'                            : '')
                +(elt.label     !== undefined ? ' label="'          + elt.label + '" ' : '')
                + ' v-model="'+elt.model+'"></input-phone>'
      return
    }
    if (elt.type == 'address') {
      buffer += '<input-address '
                +(elt.readonly    === true      ? ' :readonly="true"'                               : '')
                +(elt.required      === true    ? ' :required="true"'                               : '')
                +(elt.label       !== undefined ? ' label="'               + elt.label       + '" ' : '')
                +(elt.coordonnees !== undefined ? ' v-model:coordonnees="' + elt.coordonnees + '" ' : '')
                +(elt.onaddressSelected !== undefined ? '  @address-selected="' + elt.onaddressSelected + '" ' : '')

                + ' v-model="'+elt.model+'"></input-address>'
      return
    }
    if (elt.type == 'point') {
      buffer += '<input-point '
                +(elt.readonly   === true      ? ' :readonly="true"'                  : '')
                +(elt.required      === true      ? ' :required="true"'                            : '')
                +(elt.label      !== undefined ? ' label="'           +elt.label+'" ' : '')
                + ' v-model="'+elt.model+'"></input-point>'
      return
    }
    if (elt.type == 'siret') {
      buffer += '<input-siret '
                +(elt.readonly      === true      ? ' :readonly="true"'                                   : '')
                +(elt.required      === true      ? ' :required="true"'                            : '')
                +(elt.label         !== undefined ? ' label="'                 + elt.label         + '" ' : '')
                +(elt.fullCheck     === true      ? ' :full-check="true"'                                 : '')
                +(elt.adresse       !== undefined ? ' v-model:adresse="'       + elt.adresse       + '" ' : '')
                +(elt.raisonsociale !== undefined ? ' v-model:raisonsociale="' + elt.raisonsociale + '" ' : '')
                + ' v-model="'+elt.model+'"></input-siret>'
      return
    }
    if (elt.type == 'line') {
      buffer += '<input-line '
                +(elt.readonly      === true      ? ' :readonly="true"'                            : '')
                +(elt.required      === true      ? ' :required="true"'                            : '')
                +(elt.label         !== undefined ? ' label="'          + elt.label         + '" ' : '')
                +(elt.trim          === true      ? ' :trim="true"'                                : '')
                +(elt.simplify      === true      ? ' :simplify="true"'                            : '')
                +(elt.regex         !== undefined ? ' :regex="'         + elt.regex         + '" ' : '')
                +(elt.regexmessage  !== undefined ? ' :regexmessage="'  + elt.regexmessage  + '" ' : '')
                + ' v-model="'+elt.model+'"></input-line>'
      return
    }
    if (elt.type == 'number') {
      buffer += '<input-number '
                +(elt.readonly      === true      ? ' :readonly="true"'                            : '')
                +(elt.required      === true      ? ' :required="true"'                            : '')
                +(elt.label         !== undefined ? ' label="'          + elt.label         + '" ' : '')
                +(elt.min           !== undefined ? ' :min="'          + elt.min            + '" ' : '')
                +(elt.max           !== undefined ? ' :max="'          + elt.max            + '" ' : '')
                + ' v-model="'+elt.model+'"></input-number>'
      return
    }
    if (elt.type == 'email') {
      buffer += '<input-email '
                +(elt.readonly       === true      ? ' :readonly="true"'                            : '')
                +(elt.required       === true      ? ' :required="true"'                            : '')
                +(elt.label          !== undefined ? ' label="'          + elt.label         + '" ' : '')
                +(elt.unicity        === true      ? ' :unicity="true"'                             : '')
                +(elt.unicityexclude !== undefined ? ' :unicityexclude="'+elt.unicityexclude + '"'  : '')
                +(elt.existsAlready  !== undefined ? ' :exists-already="'+ elt.existsAlready + '" ' : '')
                + ' v-model="'+elt.model+'"></input-email>'
      return
    }
    if (elt.type == 'title') {
      buffer += '<input-title '
                +(elt.readonly       === true      ? ' :readonly="true"'                            : '')
                +(elt.options        !== undefined ? ' :options="'       + elt.options       + '"'  : '')
                + ' v-model="'+elt.model+'"></input-title>'
      return
    }'business-contact'
    if (elt.type == 'business-contact') {
      buffer += '<business-contact '
                +(elt.readonly       === true      ? ' :readonly="true"'                            : '')
                + ' v-model="'+elt.model+'"></business-contact>'
      return
    }

  });
  if (!isTemplate) {
    node[0].innerHTML = buffer
    if (removeId===true)
      node[0].removeAttribute('id')
    return
  } else {
    if (removeId===true)
      node.innerHTML = node.innerHTML.replace(new RegExp('<[^<]+ id="'+anchor+'"[^>]*></[^>]+>'), buffer)
    else
      node.innerHTML = node.innerHTML.replace(new RegExp('<([^<]+) id="'+anchor+'"([^>]*)>'), '<$1 id="'+anchor+'"$2>'+buffer)
  }
}

VueComponents= {
  'tab-panel-overview-zoom': {
    props: {
      modelValue: {
        type: String
      },
      elements: {
        type: Array,
        default: []
      },
      element: {
        type: Object,
        default: {}
      },
      textAdd: {
        type: String,
        default: 'Ajouter'
      },
    },
    emits: ['update:modelValue', 'askedAdd', 'askedLoad', 'askedBack', 'askedSubmit', 'askedReset'],
    data() {
      return {
        refForm: 'reform-'+Math.floor(Math.random()*10000+10000),
        truc: 'machin'
      }
    },
    watch: {
    },
    mounted: function() {
      var vm = this
    },
    methods: {
      submit: function() {
        var vm = this
        vm.$refs[vm.refForm].validate().then(success => {
          if (success)
            vm.$emit('askedSubmit')
        })
      },
    },
    template: `
    <q-tab-panels animated class="shadow-2 rounded-borders" style='width:100%;'
      :model-value="modelValue" @update:model-value="value => $emit('update:modelValue', value)"
      >
      <q-tab-panel flat name="overview" class='fit row wrap justify-evenly items-start content-start'>
        <div class="row items-center justify-center q-gutter-md" style='width:100%;'>
          <q-btn color="primary" icon="add" @click='$emit("askedAdd")'>{{textAdd}}</q-btn>
        </div>
        <div class="q-pa-md row items-center justify-center q-gutter-md" style='width:100%;'>
          <q-card v-for="item in elements" class='col-grow' clickable>
            <q-card-section>
              <slot name="overview" :item="item"></slot>
            </q-card-section>
            <q-card-actions align="right">
              <q-btn color="primary" icon="mode_edit" @click='$emit("askedLoad", item)'></q-btn>
            </q-card-actions>
          </q-card>
        </div>
      </q-tab-panel>

      <q-tab-panel flat name="zoom">
        <q-form :ref='refForm' @submit='submit' @reset='$emit("askedReset")'>
          <q-card>
            <q-card-section>
              <slot name="zoom" :item="element"></slot>
            </q-card-section>
            <q-card-actions align="right">
              <q-btn type="submit" label="Enregistrer"></q-btn>
              <q-btn type="reset"  label="Réinitialiser"></q-btn>
              <q-btn icon="undo" @click='$emit("askedBack")'></q-btn>
            </q-card-actions>
          </q-card>
        </q-form>
      </q-tab-panel>
  </q-tab-panels>
    `
  },
  'input-date': {
    props: {
      modelValue: {
        type: String
      },
      label: {
        type: String,
        default: 'Date'
      },
      readonly: {
        type: Boolean,
        default: false
      },
      required: {
        type: Boolean,
        default: false
      },
      subclass: {
        type: String,
        default: 'form-input col-grow q-field--with-bottom'
      },
      substyle: {
        type: String,
        default: 'width: 100%;'
      },
    },
    emits: ['update:modelValue'],
    data() {
      return {
        internalValue: null,
        popup_ref: 'popupproxy-'+Math.floor(Math.random()*10000+10000),
        showPopup: false,
        inputName: 'inputname-'+Math.floor(Math.random()*10000+10000),
      }
    },
    watch: {
      modelValue: function(newVal) {
        var vm = this
        vm.convertToInternal(newVal)
      },
      internalValue: function(newVal) {
        var vm = this
        var tmp = moment(newVal, 'DD/MM/YYYY').format('YYYY-MM-DD')
        if (vm.modelValue == tmp) return

        vm.$emit('update:modelValue', moment(newVal, 'DD/MM/YYYY').format('YYYY-MM-DD'));
      },
    },
    mounted: function() {
      var vm = this
      vm.convertToInternal(vm.modelValue)
      var input = document.getElementsByName(vm.inputName)
      if (input.length > 0)
        input[0].classList.add('pointeronly')
    },
    methods: {
      convertToInternal: function(newVal) {
        var vm = this
        vm.internalValue = moment(newVal, 'YYYY-MM-DD').format('DD/MM/YYYY')
      },
      pop: function() {
        var vm = this
        if (!vm.readonly)
          vm.showPopup = true
      }
    },
    template: `
    <q-input filled outlined readonly type="email" debounce='500'
             v-model="internalValue"
             :label='label' :readonly='readonly'  :required='required'
             :style='substyle'
             :class='subclass'
             @click='pop'
             :name="inputName"
             >
      <template v-slot:append>
        <q-icon name="event" class="cursor-pointer" @click='pop'>
          <q-popup-proxy v-model='showPopup' :ref="popup_ref" no-parent-event>
            <q-date :model-value="modelValue" @update:model-value="value => $emit('update:modelValue', value)" mask="YYYY-MM-DD">
              <div class="row items-center justify-end">
                <q-btn v-close-popup label="Fermer"></q-btn>
              </div>
            </q-date>
          </q-popup-proxy>
        </q-icon>
      </template>
      <q-tooltip v-if='modelValue != ""'>{{modelValue}}</q-tooltip>
    </q-input>
    `
  },
  'input-email': {
    props: {
      modelValue: {
        type: String
      },
      label: {
        type: String,
        default: 'Courriel'
      },
      readonly: {
        type: Boolean,
        default: false
      },
      required: {
        type: Boolean,
        default: false
      },
      autofocus: {
        type: Boolean,
        default: false
      },
      unicity: {
        type: Boolean,
        default: false
      },
      unicityexclude: {
        type: Number
      },
      subclass: {
        type: String,
        default: 'form-input col-grow q-field--with-bottom triethiccomp triethic-input'
      },
      substyle: {
        type: String,
        default: 'width: 100%;'
      },
    },
    emits: ['update:modelValue', 'existsAlready', 'uniq'],
    data() {
      return {
        showCopyToClipboard: false,
        previousMail: ''
      }
    },
    watch: {
    },
    mounted: function() {
      var vm = this
      vm.showCopyToClipboard = false
      vm.$el.addEventListener('mouseenter', e => {
        if (Quasar.Platform.is.mobile) return
        vm.showCopyToClipboard = true
      });

      vm.$el.addEventListener('mouseleave', e => {
        if (Quasar.Platform.is.mobile) return
        vm.showCopyToClipboard = false
      });
    },
    methods: {
      copyToClipboard: function(text) {
        Quasar.copyToClipboard(text)
        .then(() => {notify('positive', 'Données placées dans le presse-papier.')})
        .catch(() => {notify('negative', 'Problème d\'accès au presse-papier.')})
      },
      fieldValidator: function(val) {
        var vm = this
        var syntax = (!vm.required && val == '') || Utils.validators.email(val) || 'Courriel invalide'
        if (syntax !== true) return syntax;
        if (vm.previousMail == val) return // rien n'a changé donc rien à vérifier
        vm.previousMail = val
        if (vm.unicity !== true || syntax !== true) {vm.$emit('uniq');return syntax}
        if (val == '') {vm.$emit('uniq');return true}

        return new Promise((resolve, reject) => {
          var params = {email: val}
          if (vm.unicityexclude !== undefined)  params.contact_id = vm.unicityexclude
          post('/api/v1.0/integrateur/contact/exists', params, function(data) {
            if (!data.status) {
              notify('negative', 'Une erreur s\'est produite.<BR>Nous vous invitons essayer à nouveau et le cas échéant nous contacter.');
              resolve("Une erreur s'est produite pendant la validation")
              return
            }
            resolve(!data.result.exists || 'Cette adresse existe déjà')
            if (data.result.exists) vm.$emit('existsAlready', data.result.id)
            else vm.$emit('uniq')
            }, function(){
              notify('negative', 'Une erreur s\'est produite.<BR>Nous vous invitons essayer à nouveau et le cas échéant nous contacter.');
              resolve("Une erreur s'est produite pendant la validation")
            })
        })
      },
      clean: function() {
        var vm = this
        var tmp = vm.modelValue
        tmp = tmp.simplify()
        if (vm.modelValue != tmp)
          vm.$emit('update:modelValue', tmp)
      },
    },
    template: `
    <q-input filled outlined type="email" debounce='500'
             :model-value="modelValue" @update:model-value="value => $emit('update:modelValue', value)"
             @blur='clean'
             :label='label' :autofocus='autofocus' :readonly='readonly' :rules="[ val => fieldValidator(val)]" :required='required'
             :style='substyle'
             :class='subclass'>
      <template v-slot:prepend><q-icon name="fas fa-envelope"></q-icon></template>
      <template v-slot:append v-if='showCopyToClipboard'>
        <q-icon name="far fa-clipboard" @click='copyToClipboard(modelValue)'></q-icon>
      </template>
      <q-tooltip v-if='modelValue != ""'>{{modelValue}}</q-tooltip>
    </q-input>
    `
  },
  'input-line': {
    props: {
      modelValue: {
        type: String
      },
      trim: {
        type: Boolean,
        default: true
      },
      simplify: {
        type: Boolean,
        default: false
      },
      regex: {
        type: String,
        default: '\\w'
      },
      regexmessage: {
        type: String,
        default: 'Ce champs ne peut être vide'
      },
      label: {
        type: String,
        default: ''
      },
      readonly: {
        type: Boolean,
        default: false
      },
      required: {
        type: Boolean,
        default: false
      },
      autogrow: {
        type: Boolean,
        default: false
      },
      showCopyToClipboard: {
        type: Boolean,
        default: true
      },
      subclass: {
        type: String,
        default: 'form-input col-grow q-field--with-bottom  triethiccomp triethic-input'
      },
      substyle: {
        type: String,
        default: 'width: 100%;'
      },
      maxlength: {
        type: Number,
        default: 255
      },
      icon: {
        type: String,
        default: ''
      },
    },
    emits: ['update:modelValue'],
    data() {
      return {
        isPossibleToCopyToClipboard: false
      }
    },
    watch: {
    },
    mounted: function() {
      var vm = this
      vm.isPossibleToCopyToClipboard = false
      vm.$el.addEventListener('mouseenter', e => {
        if (Quasar.Platform.is.mobile) return
        vm.isPossibleToCopyToClipboard = true
      });

      vm.$el.addEventListener('mouseleave', e => {
        if (Quasar.Platform.is.mobile) return
        vm.isPossibleToCopyToClipboard = false
      });
    },
    methods: {
      copyToClipboard: function(text) {
        Quasar.copyToClipboard(text)
        .then(() => {notify('positive', 'Données placées dans le presse-papier.')})
        .catch(() => {notify('negative', 'Problème d\'accès au presse-papier.')})
      },
      fieldValidator: function(val) {
        var vm = this
        return (!vm.required && val == '') || RegExp(vm.regex).test(val) ? true : vm.regexmessage
      },
      clean: function() {
        var vm = this
        var tmp = vm.modelValue
        if (vm.trim === true) tmp = tmp.trim()
        if (vm.simplify === true) tmp = tmp.simplify()
        if (vm.modelValue != tmp)
          vm.$emit('update:modelValue', tmp)
      },
    },
    template: `
    <q-input filled outlined
             :model-value="modelValue" @update:model-value="value => $emit('update:modelValue', value)"
             @blur='clean'
             :label='label' :readonly='readonly' :rules="[ val => fieldValidator(val) ]" :required='required'
             :style='substyle'
             :class='subclass'
             :autogrow='autogrow' :maxlength='maxlength'>
      <template v-if='icon != ""' v-slot:prepend><q-icon :name="icon"></q-icon></template>
      <template v-slot:append v-if='isPossibleToCopyToClipboard && showCopyToClipboard'>
        <q-icon name="far fa-clipboard" @click='copyToClipboard(modelValue)'></q-icon>
      </template>
      <q-tooltip v-if='modelValue != ""'>{{modelValue}}</q-tooltip>
    </q-input>
    `
  },
  'input-checkbox': {
    props: {
      modelValue: {
        type: null
      },
      leftLabel: {
        type: Boolean,
        default: false
      },
      label: {
        type: String,
        default: ''
      },
      readonly: {
        type: Boolean,
        default: false
      },
      subclass: {
        type: String,
        default: 'form-input col-grow q-field--with-bottom'
      },
      substyle: {
        type: String,
        default: 'width: 100%;'
      },
      convertToBool: {
        type: Boolean,
        default: true
      }
    },
    emits: ['update:modelValue'],
    data() {
      return {
        internalValue: false
      }
    },
    watch: {
      modelValue: function(newVal) {
        var vm = this
        if (vm.convert(newVal)) {
          vm.internalValue = newVal
        }
      },
      internalValue: function(newVal) {


      }
    },
    mounted: function() {
      var vm = this
      vm.convert(vm.modelValue)
    },
    methods: {
      convert: function(newVal) {
        var vm = this
        if(!vm.convertToBool) return true
        if (typeof newVal == 'boolean') return true
        if (typeof newVal == 'number') {
          vm.$emit('update:modelValue', newVal == 0 ? false : true)
          return false
        }
        if (typeof newVal == 'string') {
          vm.$emit('update:modelValue', ['true', 'yes', '1'].indexOf(newVal) >= 0 ? true : false)
          return false
        }
        console.debug('input-checkbox: invalid value')
        return true
      }
    },
    template: `
    <q-checkbox
             :left-label='leftLabel' :label="label"
             :model-value="modelValue" @update:model-value="value => $emit('update:modelValue', value)"
             :style='substyle'
             :class='subclass'
             :readonly='readonly'
             >
    </q-checkbox>
    `
  },
  'input-number': {
    props: {
      modelValue: {
        type: [Number, String],
        default: 1
      },
      label: {
        type: String,
        default: ''
      },
      readonly: {
        type: Boolean,
        default: false
      },
      required: {
        type: Boolean,
        default: false
      },
      subclass: {
        type: String,
        default: 'form-input col-grow q-field--with-bottom'
      },
      substyle: {
        type: String,
        default: ''
      },
      min: {
        type: Number,
        default: 0
      },
      max: {
        type: Number,
        default: 99
      },
    },
    emits: ['update:modelValue'],
    data() {
      return {
        internalValue: 0,
        hasFocus: false
      }
    },
    watch: {
      modelValue: function(newVal) {
        var vm = this
        vm.internalValue = vm.modelValue
        if (typeof newVal == 'string')
          vm.$emit('update:modelValue', Number(newVal))
      },
      internalValue: function(newVal) {
        var vm = this
        if (vm.hasFocus) return
        vm.emitValue()
      }
    },
    mounted: function() {
      var vm = this
      vm.internalValue = vm.modelValue
    },
    methods: {
      lostFocus: function() {
        var vm = this
        vm.hasFocus = false
        vm.emitValue()
      },
      emitValue: function() {
        var vm = this
        if (vm.internalValue > vm.max) {
          vm.internalValue = vm.max
          return
        }
        if (vm.internalValue < vm.min) {
          vm.internalValue = vm.min
          return
        }
        vm.$emit('update:modelValue', vm.internalValue)
      }
    },
    template: `
    <q-input filled outlined
             v-model.number="internalValue"
             type="number"
             :label='label' :readonly='readonly' :required='required'
             :style='substyle'
             :class='subclass'
             :min='min' :max='max'
             @focus='hasFocus = true'
             @blur='lostFocus'>
      <q-tooltip>{{internalValue}}</q-tooltip>
    </q-input>
    `
  },
  'input-title': {
    props: {
      modelValue: {
        type: Number
      },
      options: {
        type: Array,
        default: [{label: 'Mme', value: 0}, {label: 'M.', value: 1}]
      },
      readonly: {
        type: Boolean,
        default: false
      },
      subclass: {
        type: String,
        default: 'form-input col-grow q-field--with-bottom'
      },
      substyle: {
        type: String,
        default: 'width: 100%;'
      },
    },
    emits: ['update:modelValue'],
    data() {
      return {
      }
    },
    watch: {
    },
    mounted: function() {
      var vm = this
    },
    methods: {
      copyToClipboard: function(text) {
        Quasar.copyToClipboard(text)
        .then(() => {notify('positive', 'Données placées dans le presse-papier.')})
        .catch(() => {notify('negative', 'Problème d\'accès au presse-papier.')})
      },
    },
    template: `
    <q-btn-toggle
      :model-value="modelValue" @update:model-value="value => $emit('update:modelValue', value)"
      :options="options"
      :readonly='readonly'
      :style='substyle'
      :class='subclass'
      spread
      class="my-custom-toggle"
      no-caps
      rounded
      unelevated
      toggle-color="primary"
      color="white"
      text-color="primary"
    ></q-btn-toggle>
    `
  },
  'input-phone': {
    props: {
      modelValue: {
        type: String
      },
      label: {
        type: String,
        default: 'Téléphone'
      },
      readonly: {
        type: Boolean,
        default: false
      },
      required: {
        type: Boolean,
        default: false
      },
      subclass: {
        type: String,
        default: 'form-input col-grow q-field--with-bottom triethiccomp triethic-input'
      },
      substyle: {
        type: String,
        default: 'width: 100%;'
      },
    },
    emits: ['update:modelValue'],
    data() {
      return {
        showCopyToClipboard: false
      }
    },
    watch: {
      modelValue: function(newVal) {
        var vm = this
        //if (!vm.siretValidator(newVal)) return
      }
    },
    mounted: function() {
      var vm = this
      vm.showCopyToClipboard = false
      vm.$el.addEventListener('mouseenter', e => {
        if (Quasar.Platform.is.mobile) return
        vm.showCopyToClipboard = true
      });

      vm.$el.addEventListener('mouseleave', e => {
        if (Quasar.Platform.is.mobile) return
        vm.showCopyToClipboard = false
      });
    },
    methods: {
      copyToClipboard: function(text) {
        Quasar.copyToClipboard(text)
        .then(() => {notify('positive', 'Données placées dans le presse-papier.')})
        .catch(() => {notify('negative', 'Problème d\'accès au presse-papier.')})
      },
      fieldValidator: function(val) {
        var vm = this
        return (!vm.required && val == '') || libphonenumber.isValidNumber(val, 'FR') || 'Numéro invalide'
      },
    },
    template: `
    <q-input filled outlined
             :model-value="modelValue" @update:model-value="value => $emit('update:modelValue', value)"
             :label='label' :readonly='readonly' mask='## ## ## ## ##' :unmasked-value='true' :rules="[ val => fieldValidator(val) ]"
             :required='required'
             :style='substyle'
             :class='subclass'>
      <template v-if='label == "Téléphone"' v-slot:prepend><q-icon name="fas fa-phone"></q-icon></template>
      <template v-else v-slot:prepend><q-icon name="fas fa-mobile-alt"></q-icon></template>
      <template v-slot:append v-if='showCopyToClipboard'>
        <q-icon name="far fa-clipboard" @click='copyToClipboard(modelValue)'></q-icon>
      </template>
      <q-tooltip v-if='modelValue != ""'>{{modelValue}}</q-tooltip>
    </q-input>
    `
  },
  'input-siret': {
    props: {
      modelValue: {
        type: String
      },
      fullCheck: {
        type: Boolean,
        default: false
      },
      raisonsociale: {
        type: String
      },
      adresse: {
        type: String
      },
      label: {
        type: String,
        default: 'SIRET'
      },
      readonly: {
        type: Boolean,
        default: false
      },
      required: {
        type: Boolean,
        default: false
      },
      subclass: {
        type: String,
        default: 'form-input col-grow q-field--with-bottom'
      },
      substyle: {
        type: String,
        default: 'width: 100%;'
      },
    },
    emits: ['update:modelValue', 'update:raisonsociale', 'update:adresse'],
    data() {
      return {
        showCopyToClipboard: false
      }
    },
    watch: {
      modelValue: function(newVal) {
        var vm = this
        if (vm.fieldValidator(newVal) !== true) return
        if (!vm.fullCheck) return
        var params = {
          headers: {
            'Accept': 'application/json',
            'Authorization': 'Bearer e9824d36-ad7b-3e44-94c3-0207d0b366b0'
          }
        };
        axios.get('https://api.insee.fr/entreprises/sirene/V3/siret/'+newVal, params)
        .then(function (response) {
          if (response.status == 200) {
            var item = response.data.etablissement
            vm.$emit('update:raisonsociale', item.uniteLegale.denominationUniteLegale)
            var buffer = '';
            if (item.adresseEtablissement.numeroVoieEtablissement)       buffer += item.adresseEtablissement.numeroVoieEtablissement
            if (item.adresseEtablissement.indiceRepetitionEtablissement) buffer += ' '  + item.adresseEtablissement.indiceRepetitionEtablissement
            if (item.adresseEtablissement.typeVoieEtablissement)         buffer += ' '  + item.adresseEtablissement.typeVoieEtablissement
            if (item.adresseEtablissement.libelleVoieEtablissement)      buffer += ' '  + item.adresseEtablissement.libelleVoieEtablissement
            if (item.adresseEtablissement.codePostalEtablissement)       buffer += ', ' + item.adresseEtablissement.codePostalEtablissement
            if (item.adresseEtablissement.libelleCommuneEtablissement)   buffer += ' '  + item.adresseEtablissement.libelleCommuneEtablissement.replace(/ *[0-9]+ */, '')
            buffer.simplify().replace(/^,/, '').replace(/,$/, '').simplify()
            vm.$emit('update:adresse', buffer)
          }
        })
        .catch(function (error) {
          console.debug(error)
        })
        .then(function () {
          // always executed
        });
      }
    },
    mounted: function() {
      var vm = this
      vm.showCopyToClipboard = false
      vm.$el.addEventListener('mouseenter', e => {
        if (Quasar.Platform.is.mobile) return
        vm.showCopyToClipboard = true
      });

      vm.$el.addEventListener('mouseleave', e => {
        if (Quasar.Platform.is.mobile) return
        vm.showCopyToClipboard = false
      });
    },
    methods: {
      copyToClipboard: function(text) {
        Quasar.copyToClipboard(text)
        .then(() => {notify('positive', 'Données placées dans le presse-papier.')})
        .catch(() => {notify('negative', 'Problème d\'accès au presse-papier.')})
      },
      fieldValidator: function(val) {
        var vm = this
        return (!vm.required && val == '') || Utils.validators.siret(val) || 'Le SIRET est invalide'
      },
    },
    template: `
    <q-input filled outlined debounce='500'
             :model-value="modelValue" @update:model-value="value => $emit('update:modelValue', value)"
             :label='label' :readonly='readonly' mask='### ### ### #####' :unmasked-value='true' :rules="[ val => fieldValidator(val) ]"
             :required='required'
             :style='substyle'
             :class='subclass'>
      <template v-slot:append v-if='showCopyToClipboard'>
        <q-icon name="far fa-clipboard" @click='copyToClipboard(modelValue)'></q-icon>
      </template>
      <q-tooltip v-if='modelValue != ""'>{{modelValue}}</q-tooltip>
    </q-input>
    `
  },
  'input-address': {
    props: {
      modelValue: {
        type: String
      },
      coordonnees: {
        type: String
      },
      label: {
        type: String,
        default: 'Adresse'
      },
      readonly: {
        type: Boolean,
        default: false
      },
      required: {
        type: Boolean,
        default: false
      },
      subclass: {
        type: String,
        default: 'form-input col-grow q-field--with-bottom'
      },
      substyle: {
        type: String,
        default: 'width: 100%;'
      },
    },
    emits: ['update:modelValue', 'update:coordonnees', 'addressSelected'],
    data() {
      return {
        addressDiag: false,
        addressesFound: [],
        showCopyToClipboard: false
      }
    },
    watch: {
      modelValue: function(newVal) {
        var vm = this
        vm.search(newVal)
      }
    },
    mounted: function() {
      var vm = this
      vm.showCopyToClipboard = false
      vm.$el.addEventListener('mouseenter', e => {
        if (Quasar.Platform.is.mobile) return
        vm.showCopyToClipboard = true
      });

      vm.$el.addEventListener('mouseleave', e => {
        if (Quasar.Platform.is.mobile) return
        vm.showCopyToClipboard = false
      });
    },
    methods: {
      search: function(newVal) {
        var vm = this
        if (newVal.length < 10) return
        if (vm.readonly) return
        Utils.misc.look4(newVal, function (response) {
          vm.addressesFound.splice(0, vm.addressesFound.length)
          _.forEach(response.results, function(val) {
            vm.addressesFound.push({
              label: val.fulltext,
              coordonnees: 'POINT('+val.y+' '+val.x+')'})
          })
        })
      },
      copyToClipboard: function(text) {
        Quasar.copyToClipboard(text)
        .then(() => {notify('positive', 'Données placées dans le presse-papier.')})
        .catch(() => {notify('negative', 'Problème d\'accès au presse-papier.')})
      },
      inputClicked: function() {
        var vm = this
        if (vm.readonly == false) {
          vm.addressDiag = true
          vm.search(vm.modelValue)
        }
      },
      addressSelection: function(item) {
        var vm = this
        vm.item = item
        vm.$emit('update:coordonnees', item.coordonnees)
        vm.$emit('update:modelValue', item.label)
        vm.$emit('addressSelected', {address: item.label, coordonnees: item.coordonnees})
      },
      showing: function(item) {
        var vm = this
        vm.item = null
      },
      closing: function(item) {
        var vm = this
        if (vm.item == null) {
          var existing = _.find(vm.addressesFound, {label: vm.modelValue})
          if (existing != undefined)
            vm.$emit('update:coordonnees', existing.coordonnees)
          else
            vm.$emit('update:coordonnees', 'POINT(0.00001 0.00001)')
        }
      },
      mouseenter: function() {
        var vm = this
        if (Quasar.Platform.is.mobile) return
        vm.showCopyToClipboard = true
      },
      mouseleave: function() {
        var vm = this
        if (Quasar.Platform.is.mobile) return
        vm.showCopyToClipboard = false
      },
      fieldValidator: function(val) {
        var vm = this
        return (!vm.required && val == '') || val.length > 0 || 'L\'adresse est invalide'
      },
    },
    template: `
    <q-input filled outlined debounce='750'
             :model-value="modelValue" @update:model-value="value => $emit('update:modelValue', value)"
             :label='label' @click='inputClicked' readonly
             :required='required'
             :rules="[ val => fieldValidator(val)]"
             :style='substyle'
             :class='subclass'>
      <template v-slot:append v-if='showCopyToClipboard'>
        <q-icon name="far fa-clipboard" class='cursor-pointer' @click='copyToClipboard(modelValue)'></q-icon>
      </template>
      <q-tooltip v-if='modelValue != ""'>{{modelValue}}</q-tooltip>
      <q-dialog v-model="addressDiag" @before-hide='closing' @before-show='showing' persistent>
        <q-card style='width: 40rem;'>
          <q-card-section>
            <div class="text-h6">Adresse</div>
          </q-card-section>

          <q-card-section class="q-pt-none">
            <div class="q-pa-md fit row wrap justify-center items-start content-start">
              <div class="row items-start" style='width: 100%;'>
                <q-input autofocus debounce='500' filled outlined  class='form-input' label='Adresse'
                         :model-value="modelValue" @update:model-value="value => $emit('update:modelValue', value)"
                ></q-input>
              </div>
              <div class="row items-start" style='width: 100%;'>
              Adresses correspondantes :
              </div>
              <div class="row items-start" style='width: 100%;'>
                <div style="width: 100%">
                  <q-virtual-scroll
                    style="max-height: 10rem;min-height: 10rem"
                    :items="addressesFound"
                    separator
                  >
                  <template v-slot="{ item, index }">
                    <q-item
                      :key="index"
                      dense
                      clickable v-ripple
                      @click="addressSelection(item)"
                      v-close-popup
                    >
                      <q-item-section>
                        <q-item-label>{{ item.label }}</q-item-label>
                      </q-item-section>
                    </q-item>
                  </template>
                  </q-virtual-scroll>
                </div>
              </div>
            </div>
          </q-card-section>

          <q-card-actions align="right">
            <q-btn label="Fermer" v-close-popup></q-btn>
          </q-card-actions>
        </q-card>
      </q-dialog>
    </q-input>
    `
  },
  'input-point': {
    props: {
      modelValue: {
        type: String //Point(48.88379165333081, 2.1960164290841027) Point(Latitude Longitude)
      },
      label: {
        type: String,
        default: 'Coordonnées'
      },
      readonly: {
        type: Boolean,
        default: false
      },
      required: {
        type: Boolean,
        default: false
      },
      subclass: {
        type: String,
        default: 'form-input col-grow q-field--with-bottom'
      },
      substyle: {
        type: String,
        default: 'width: 100%;'
      },
      valid: {
        type: Boolean,
        default: false
      },
    },
    emits: ['update:modelValue', 'update:valid'],
    data() {
      return {
        internalValue: '',
        showMap: false,
        map: '',
        mapId: 'map-'+Math.floor(Math.random()*10000+10000),
        regexp: /.*?(-?[0-9+]+\.[0-9+]+) +(-?[0-9+]+\.[0-9+]+).*/,
        inputRef: 'input-'+Math.floor(Math.random()*10000+10000),
        caretPosition: 0,
        showCopyToClipboard: false
      }
    },
    watch: {
      modelValue: function(newVal) {
        var vm = this
        vm.internalValue = newVal.replace(vm.regexp, '$1 $2')
      },
      internalValue: function(newVal) {
        var vm = this
        if (!vm.regexp.test(newVal)) return
        vm.$emit('update:modelValue', 'POINT('+newVal+')')
      },
      showMap: function(newVal) {
        var vm = this
        if (newVal) {
          this.$nextTick(function() {
            vm.displayMap()
          })
        }
      },
    },
    mounted: function() {
      var vm = this
      vm.showCopyToClipboard = false
      vm.$el.addEventListener('mouseenter', e => {
        if (Quasar.Platform.is.mobile) return
        vm.showCopyToClipboard = true
      });

      vm.$el.addEventListener('mouseleave', e => {
        if (Quasar.Platform.is.mobile) return
        vm.showCopyToClipboard = false
      });
      vm.internalValue = vm.modelValue.replace(vm.regexp, '$1 $2')
      vm.$refs[vm.inputRef].getNativeElement().addEventListener('paste', (event) => {
        let paste = (event.clipboardData || window.clipboardData).getData('text');
        paste = paste.replace(/[^\d. -]/g, '')
        if (vm.regexp.test(paste))
          vm.internalValue = paste
        event.preventDefault();
    });
    },
    methods: {
      destroyMap: function() {
        var vm = this
        vm.map.destroyMap()
      },
      displayMap() {
        var vm = this
        vm.map = Utils.misc.displayMap(vm.mapId, vm.modelValue.replace(vm.regexp, '$1')
                                               , vm.modelValue.replace(vm.regexp, '$2')
                                      );
      },
      fieldValidator: function(val) {
        var vm = this
        if (vm.readonly) {
          vm.$emit('update:valid', true)
          return true
        }
        vm.$emit('update:valid', (!vm.required && val == '') || /^-?[0-9]+\.[0-9]+ -?[0-9]+\.[0-9]+$/.test(val))
        return (!vm.required && val == '') || /^-?[0-9]+\.[0-9]+ -?[0-9]+\.[0-9]+$/.test(val) || 'Coordonnées invalides ; un exemple : 48.8833683420629 2.1947289688220133'
      },
    },
    template: `
    <q-input  filled outlined
      v-model='internalValue'
      :ref='inputRef'
      type="text"
      :label='label'
      :rules="[ val => fieldValidator(val)]"
      :required='required'
      :style='substyle'
      :class='subclass'
      :readonly='readonly'
    >
      <template v-slot:append>
        <q-icon name="map" @click='showMap = true' class="cursor-pointer"></q-icon>
      </template>
      <q-tooltip v-if='modelValue != ""'>{{modelValue}}</q-tooltip>
    </q-input>
    <q-dialog v-model="showMap" @before-hide="destroyMap">
      <q-card style='max-width: none;'>
        <q-card-section>
          <div :id='mapId' style='height: 70vh;width: 70vw;max-width: none;'></div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="OK" color="primary" v-close-popup></q-btn>
        </q-card-actions>
      </q-card>
    </q-dialog>
    `
  },
  'input-select': {
    props: {
      modelValue: Number,
      label: {
        type: String,
        default: 'Veuillez faire votre sélection'
      },
      readonly: {
        type: Boolean,
        default: false
      },
      required: {
        type: Boolean,
        default: false
      },
      subclass: {
        type: String,
        default: 'form-input col-grow q-field--with-bottom'
      },
      substyle: {
        type: String,
        default: 'width: 100%;'
      },
      options: {
        type: Array,
        default: false
      },
      disable: {
        type: Boolean,
        default: false
      },
      useInput: {
        type: Boolean,
        default: true
      },
      clearable: {
        type: Boolean,
        default: true
      },
    },
    emits: ['update:modelValue'],
    data() {
      return {
        entitiesFiltered: [],
        internalValue: null
      }
    },
    watch: {
      internalValue: function(newVal) {
        var vm = this
        if (newVal === undefined) return

        if (vm.modelValue == newVal) return
        if (newVal == null) {
          vm.$emit('update:modelValue', null)
          return
        }
        vm.$emit('update:modelValue', newVal.value)
      },
      modelValue: function(newVal) {
        var vm = this
        vm.loadModelValue()
      },
    },
    mounted: function() {
      var vm = this
      vm.loadModelValue()
    },
    methods: {
      loadModelValue: function() {
        var vm = this
        if (vm.modelValue == null || vm.modelValue == undefined) {
          vm.internalValue = null
          return
        }
        if (vm.internalValue == null || vm.modelValue != vm.internalValue.value) {
          vm.internalValue = _.find(vm.options, function(o) { return o.value == vm.modelValue; });
          return
        }
      },
      filterEntities: function(val, update) {
        var vm = this
        if (val === '') {
          update(() => {
            vm.entitiesFiltered = vm.options
          })
          return
        }
        update(() => {
          const needle = val.toLowerCase()
          vm.entitiesFiltered = vm.options.filter(v => v.label.toLowerCase().indexOf(needle) > -1)
        })
      },
    },
    template: `
    <q-select
      :clearable='clearable'
      filled outlined
      v-model="internalValue"
      :use-input='useInput'
      input-debounce="0"
      :label='label'
      :options="entitiesFiltered"
      :required='required'
      :readonly='readonly'
      @filter="filterEntities"
      :style='substyle'
      :class='subclass'
      :disable='disable'
    >
    <template v-slot:no-option>
      <q-item>
        <q-item-section class="text-grey">
          Pas de correspondance
        </q-item-section>
      </q-item>
    </template>
  </q-select>
    `
  },
  'input-select-v2': {
    props: {
      modelValue: [String, Number, Object, Boolean, Date],
      label: {
        type: String,
        default: 'Veuillez faire votre sélection'
      },
      readonly: {
        type: Boolean,
        default: false
      },
      required: {
        type: Boolean,
        default: false
      },
      subclass: {
        type: String,
        default: 'form-input col-grow q-field--with-bottom'
      },
      substyle: {
        type: String,
        default: 'width: 100%;'
      },
      options: {
        type: Array,
        default: false
      },
      optionValue: {
        type: String,
        default: 'value'
      },
      optionLabel: {
        type: String,
        default: 'label'
      },
      optionDisable: {
        default: ''
      },
      emitValue: {
        type: Boolean,
        default: false
      },
      inputDebounce: {
        type: Number,
        default: 0
      },
      disable: {
        type: Boolean,
        default: false
      },
      useInput: {
        type: Boolean,
        default: true
      },
      clearable: {
        type: Boolean,
        default: true
      },
    },
    emits: ['update:modelValue'],
    data() {
      return {
        entitiesFiltered: [],
        internalValue: null
      }
    },
    watch: {
      internalValue: function(newVal) {
        var vm = this
        if (newVal === undefined) return

        if (vm.modelValue == newVal) return
        if (newVal == null) {
          vm.$emit('update:modelValue', null)
          return
        }
        if (vm.emitValue)
          vm.$emit('update:modelValue', newVal[vm.optionValue])
        else
          vm.$emit('update:modelValue', newVal)
      },
      modelValue: function(newVal) {
        var vm = this
        vm.loadModelValue()
      },
    },
    mounted: function() {
      var vm = this
      vm.loadModelValue()
    },
    methods: {
      loadModelValue: function() {
        var vm = this
        if (vm.modelValue == null || vm.modelValue == undefined) {
          vm.internalValue = null
          return
        }
        if (vm.emitValue) {
          if (vm.internalValue == null || vm.modelValue != vm.internalValue[vm.optionValue]) {
            vm.internalValue = _.find(vm.options, function(o) { return o[vm.optionValue] == vm.modelValue; });
            return
          }
        }else{
          if (vm.internalValue == null || vm.modelValue[vm.optionValue] != vm.internalValue[vm.optionValue]) {
            vm.internalValue = _.find(vm.options, function(o) { return o[vm.optionValue] == vm.modelValue[vm.optionValue]; });
            return
          }
        }
      },
      filterEntities: function(val, update) {
        var vm = this
        if (val === '') {
          update(() => {
            vm.entitiesFiltered = vm.options
          })
          return
        }
        update(() => {
          const needle = val.toLowerCase()
            vm.entitiesFiltered = vm.options.filter(v => v[vm.optionLabel].toLowerCase().indexOf(needle) > -1)
        })
      },
    },
    template: `
    <q-select
      :clearable='clearable'
      filled outlined
      v-model="internalValue"
      :use-input='useInput'
      :input-debounce='inputDebounce'
      :label='label'
      :options="entitiesFiltered"
      :required='required'
      :readonly='readonly'
      :option-label='optionLabel'
      :option-value='optionValue'
      :option-disable="optionDisable"
      map-options
      @filter="filterEntities"
      :style='substyle'
      :class='subclass'
      :disable='disable'
    >
    <template v-slot:no-option>
      <q-item>
        <q-item-section class="text-grey">
          Pas de correspondance
        </q-item-section>
      </q-item>
    </template>
  </q-select>
    `
  },
  'input-color': {
    props: {
      modelValue: String,
      label: {
        type: String,
        default: 'Couleur'
      },
      readonly: {
        type: Boolean,
        default: false
      },
      required: {
        type: Boolean,
        default: false
      },
      subclass: {
        type: String,
        default: 'form-input col-grow q-field--with-bottom'
      },
      substyle: {
        type: String,
        default: 'width: 100%;'
      },
      disable: {
        type: Boolean,
        default: false
      },
      type: {
        type: String,
        default: ''
      },
    },
    emits: ['update:modelValue', 'changed'],
    data() {
      return {
        entitiesFiltered: [],
        internalValue: null
      }
    },
    watch: {
      internalValue: function(newVal) {
        var vm = this
        if (newVal === undefined) return

        if (vm.modelValue == newVal) return
        if (newVal == null) {
          vm.$emit('update:modelValue', null)
          return
        }
        vm.$emit('update:modelValue', newVal)
        vm.$emit('changed', newVal)
      },
      modelValue: function(newVal) {
        var vm = this
        vm.internalValue = newVal
      },
    },
    mounted: function() {
      var vm = this
      vm.internalValue = vm.modelValue
    },
    methods: {
      oppositeColor: function (val) {
        return Utils.misc.oppositeColor(val)
      },
      fieldValidator: function(val) {
        var vm = this
        return (!vm.required && val == '') || Quasar.patterns.testPattern.anyColor(val) ? true : 'Couleur invalide'
      },
    },
    template: `
    <div v-if='type == "badge"' :style='substyle' :class='subclass'>
      <q-badge :color="internalValue" style='cursor:pointer;' :style='{backgroundColor: internalValue, fontWeight: "bold", color:oppositeColor(internalValue)}'>
        {{ internalValue }}
      </q-badge>
      <q-popup-edit v-model="internalValue" title="Quantité en stock" v-slot="scope" buttons label-set='Enregistrer' @save='internalValue = value'>
        <q-color default-view="tune" format-model='hex' v-model="scope.value"></q-color>
      </q-popup-edit>
    </div>
    <q-input v-if='type != "badge"'
        filled outlined
        :rules="[ val => fieldValidator(val) ]"
        v-model="internalValue"
        :label='label'
        :required='required'
        :readonly='readonly'
        :style='substyle'
        :class='subclass'
        :disable='disable'
      >
      <template v-slot:append>
        <q-icon name="colorize" class="cursor-pointer" :style='{backgroundColor: internalValue, fontWeight: "bold", color:oppositeColor(internalValue), borderRadius: "0.2rem"}'>
          <q-popup-proxy transition-show="scale" transition-hide="scale">
            <q-color  default-view="tune" format-model='hex' v-model="internalValue"></q-color>
          </q-popup-proxy>
        </q-icon>
      </template>
    </q-input>
    `
  },
  'input-multiline': {
    props: {
      modelValue: String,
      label: {
        type: String,
        default: ''
      },
      readonly: {
        type: Boolean,
        default: false
      },
      disable: {
        type: Boolean,
        default: false
      },
      required: {
        type: Boolean,
        default: false
      },
      subclass: {
        type: String,
        default: 'form-input col-grow q-field--with-bottom'
      },
      substyle: {
        type: String,
        default: 'width: 100%;'
      },
      autogrow: {
        type: Boolean,
        default: true
      },
      maxlength:  {
        type: Number,
        default: 255
      },
      debounce:  {
        type: Number,
        default: 750
      },
    },
    data() {
      return {
        showCopyToClipboard: false
      }
    },
    watch: {
    },
    mounted: function() {
      var vm = this
      vm.showCopyToClipboard = false
      vm.$el.addEventListener('mouseenter', e => {
        if (Quasar.Platform.is.mobile) return
        vm.showCopyToClipboard = true
      });

      vm.$el.addEventListener('mouseleave', e => {
        if (Quasar.Platform.is.mobile) return
        vm.showCopyToClipboard = false
      });
    },
    methods: {
      copyToClipboard: function(text) {
        Quasar.copyToClipboard(text)
        .then(() => {notify('positive', 'Données placées dans le presse-papier.')})
        .catch(() => {notify('negative', 'Problème d\'accès au presse-papier.')})
      },
      mouseenter: function() {
        var vm = this
        if (Quasar.Platform.is.mobile) return
        vm.showCopyToClipboard = true
      },
      mouseleave: function() {
        var vm = this
        if (Quasar.Platform.is.mobile) return
        vm.showCopyToClipboard = false
      },
    },
    template: `
    <q-input filled outlined class='form-input'
             v-model='modelValue'
             :readonly='readonly'
             :label='label'
             :maxlength='maxlength'
             :autogrow='autogrow'
             :required='required'
             :style='substyle'
             :class='subclass'
             :disable='disable'
             :debounce='debounce'
    >
      <template v-slot:append v-if='showCopyToClipboard'>
        <q-icon name="far fa-clipboard" class='cursor-pointer' @click='copyToClipboard(modelValue)'></q-icon>
      </template>
    </q-input>
    `
  },
  'ghost-wrapper': {
    /*
    Dispo: props.identical => si aucune modification faire
    Dispo: props.reset()   => méthode pour reset les modifs

  <ghost-wrapper v-model:one='one'>
    <template v-slot='props'>
      <q-input v-model='props.clones.one'></q-input>
    </template>
  </ghost-wrapper>

    */
    props:['one', 'two', 'three', 'four', 'five'],
    emits: ['update:one', 'update:two', 'update:three', 'update:four', 'update:five'],
    data() {
      return {
        clones: {
          one: null,
          two: null,
          three: null,
          four: null,
          five: null
        },
        originals: {
          one: null,
          two: null,
          three: null,
          four: null,
          five: null
        },
        identical: true,
        internal: {
          identical: {}
        }
      }
    },
    watch: {
    },
    mounted: function() {
      var vm = this
      Array('one', 'two', 'three', 'four', 'five').forEach(function(value){
        vm.clones[value]    = _.cloneDeep(vm[value])
        vm.originals[value] = _.cloneDeep(vm[value])
        vm.internal.identical[value] = true
        vm.$watch(value, (newVal, oldVal) => {
          vm.clones[value]    = _.cloneDeep(newVal)
          vm.originals[value] = _.cloneDeep(newVal)
        })
        vm.$watch('clones.'+value, (newVal, oldVal) => {
          vm.internal.identical[value] = _.isEqual(vm.clones[value], vm.originals[value])
          vm.hasChanged()
        }, {deep: true})
      })
    },
    methods: {
      hasChanged: function() {
        var vm = this
        vm.identical = _.reduce(vm.internal.identical, function(acc, value, key) {
          return acc && value
        }, true)
      },
      reset: function() {
        var vm = this
        Array('one', 'two', 'three', 'four', 'five').forEach(function(value){
          vm.clones[value] = _.cloneDeep(vm.originals[value])
        })
      }
    },
    template: `
      <slot :clones="clones" :originals="originals" :identical="identical" :reset='reset'></slot>
    `
  },
  'zoomist': {//requiert Zoomist
    props: {
      modelValue: String,
    },
    data() {
      return {
        id: 'zoomist-'+Math.floor(Math.random()*100000),
        zoomist: null
      }
    },
    watch: {
    },
    beforeUnmount: function() {
      var vm = this
      vm.zoomist.destroy()
    },
    mounted: function() {
      var vm = this

      vm.zoomist = new Zoomist('#'+vm.id, {
        slider: true,
        zoomer: true,
        wheelable: false,
        fill: 'contain'
      })
    },
    methods: {
    },
    template: `
    <div :id='id' class='zoomist' :data-zoomist-src='modelValue'
    >
      <i class="fa-solid fa-up-right-from-square" style='z-index: 2;position: absolute;bottom: 0.1em;left: 0.1em;font-size: 2em;cursor: pointer;' @click='$emit("download")'></i>
    </div>
    `
  },
}
function whenAvailable(name, callback) {
  var interval = 10; // ms
  window.setTimeout(function() {
      if (window[name]) {
          callback(window[name]);
      } else {
          whenAvailable(name, callback);
      }
  }, interval);
}
//https://stackoverflow.com/questions/667555/how-to-detect-idle-time-in-javascript-elegantly
/*
  inactivityMonitor({
    lock: function(){console.debug('lock the screen')},
    warn: function(){console.debug('warn of the coming lock')},
    coolOff: function(){console.debug('dismiss the warning')},
    ping: function(){console.debug('perform a ping')},
    timeBeforeWarning: 60*1000, //in ms
    timeBeforeLock: 60*1000,      //in ms
    timeBeforePing: 2*1000,    //in ms
    granularity: 1000,
  })
*/
var inactivityMonitor = function (vm) {
  var lastEvent = Date.now()
  var timers = {
    thresholdTimer: null,
    pingTimer     : null,
    lockTimer     : null,
  }
  var state = 'STOPPED' // or LOCKED, or ACTIVE, or WARNING, or STOPPED
  var events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'];


  var checker = function() {
    if (Date.now() - lastEvent < vm.timeBeforeWarning) return
    clearInterval(timers.thresholdTimer);
    clearInterval(timers.pingTimer);
    state = 'WARNING'
    if (typeof vm.warn == 'function') vm.warn()
    timers.lockTimer = setTimeout(function(){
      state = 'LOCKED'
      vm.lock()
      stop()
    }, vm.timeBeforeLock)
  }
  var eventDetected = function(event) {
    if (event.target && event.target.classList)
      for (var i = 0 ; i < event.target.classList.length ; i++)
        if (event.target.classList.item(i).startsWith('phpdebugbar')) return

    if(state == 'LOCKED') return
    lastEvent = Date.now()
    if (state == 'WARNING') {
      clearTimeout(timers.lockTimer);
      state = 'ACTIVE'
      vm.coolOff()
      timers.thresholdTimer = setInterval(checker, vm.granularity);
    }
  }
  var stop = function() {
    state = 'STOPPED'
    clearTimeout(timers.lockTimer);
    clearInterval(timers.thresholdTimer);
    clearInterval(timers.pingTimer);
    timers.thresholdTimer = timers.pingTimer = timers.lockTimer = null
    //window.removeEventListener('load', eventDetected, true);
    events.forEach(function(name) {document.removeEventListener(name, eventDetected, true);});
  }
  var start = function() {
    if (state != 'STOPPED') return
    state = 'ACTIVE'
    if (vm.timeBeforePing != undefined)
      timers.pingTimer = setInterval(function(){if (state != 'LOCKED') if (typeof vm.ping == 'function') vm.ping()}, vm.timeBeforePing)
    timers.thresholdTimer = setInterval(checker, vm.granularity);
    //window.addEventListener('load', eventDetected, true);
    events.forEach(function(name) {document.addEventListener(name, eventDetected, true);});
  }
  return {stop: stop, start: start}
}