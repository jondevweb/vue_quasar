
VueBusinessComponents= {
  'contact-add': {
    props: {
      allowExisting: {
        type: Boolean,
        default: false
      },
      allowCancel: {
        type: Boolean,
        default: true
      },
      spinner: {
        type: Boolean,
        default: false
      },
      contactType: {
        type: String,
        default: 'client'
      },
      legalMainChecked: {
        type: Boolean,
        default: false
      },
    },
    emits: ['askedCreate', 'askedSelect', 'update:spinner', 'askedCancel'],
    data() {
      return {
        step: 1,
        regularUser: true,
        duplicateMail: false,
        contact_id: -1,
        contact: {
            email: '',
            civilite:0,
            prenom:'',
            nom:'',
            telephone:'',
            portable:'',
            poste:'',
            actif: true,
            contact_juridique: 0,
            contact_principal: 0,
            couleur: 0,
        },
      }
    },
    watch: {
      regularUser: function(newVal) {
        var vm = this
        if (newVal == false)
          vm.contact.email = ''
      },
    },
    mounted: function() {
      var vm = this
      vm.contact.contact_juridique = vm.legalMainChecked ? 1 : 0
      vm.contact.contact_principal = vm.legalMainChecked ? 1 : 0
      if (vm.contactType == 'worker' || vm.contactType == 'exutoire') {
        vm.contact.civilite = 1
        vm.$refs.stepper.next()
      }
    },
    methods: {
      checkMail:function(mail) {
        return Utils.validators.email(mail)
      },
      stepContinue: function(step) {
        var vm = this
        if (step === 4) {
          vm.$emit('askedCreate', vm.contact, vm.regularUser)
          vm.$emit('update:spinner', true)
          return
        }
        if (step != 2) {
          vm.$refs.stepper.next()
          return
        }
        var toContinue = (vm.contact.email == '' && !vm.regularUser) || (vm.checkMail(vm.contact.email) && !vm.duplicateMail)
        if (!toContinue)
          notify('info', "Le courriel saisi n'est pas valide.")
        else
          vm.$refs.stepper.next()
      },
      existsAlready: function(contact_id) {
        var vm = this
        vm.duplicateMail=true
        vm.contact_id = contact_id
      },
    },
    template: `
    <q-stepper
      v-model="step"
      ref="stepper"
      color="primary"
      animated
    >
      <template v-if='contactType == "client" || contactType == "gestionnaire"'>
        <q-step
          :name="1"
          title="Type de contact"
          icon="settings"
          :done="step > 1"
        >
          <p>Le contact peut être de deux types :</p>
          <ul>
            <li>simple, pour lequel il s'agit de conserver ses coordonnées<sup><em>*</em></sup></li>
            <li>usager du portail, lequel pourra se connecter à la plateforme</li>
          </ul>
          <div style='width:100%;text-align: center;'>
            <q-toggle
              class='toggle-choice'
              style='width:12rem'
              v-model="regularUser"
              checked-icon="fas fa-user"
              color="red"
              :label="regularUser ? 'Usager du portail' : 'Simple contact'"
              unchecked-icon="fas fa-address-card"
              size="5rem"
              keep-color
            ></q-toggle>
          </div>
          <em><sup>*</sup>Il est possible de transformer un « simple contact » en « usager du site » plus tard.</em>
        </q-step>

        <q-step
          :name="2"
          title="Courriel"
          icon="fas fa-envelope"
          :done="step > 2"
        >
          <input-email @exists-already='existsAlready' @uniq='duplicateMail=false' :autofocus='true' v-model='contact.email' :unicity="true"></input-email>
          <a @click="$emit('askedSelect', contact_id)" href='javascript:void(0)' v-show='duplicateMail && allowExisting'>Désirez-vous utiliser ce contact au lieu d'en créer un nouveau ?</a>
        </q-step>

        <q-step
          :name="3"
          title="Informations diverses"
          icon="fas fa-address-card"
          :done="step > 3"
        >
          <input-title                   v-model="contact.civilite" ></input-title>
          <input-line  label="Prénom"    v-model="contact.prenom"   ></input-line>
          <input-line  label="Nom"       v-model="contact.nom"      ></input-line>
          <input-line  label="Poste"     v-model="contact.poste"    ></input-line>
          <input-phone label="Téléphone" v-model="contact.telephone"></input-phone>
          <input-phone label="Portable"  v-model="contact.portable" ></input-phone>
          <q-card-section>
            <q-checkbox v-model="contact.contact_principal" label="Contact principal" :true-value='1' :false-value='0' v-if='contactType == "client"'></q-checkbox>
            <q-checkbox v-model="contact.contact_juridique" label="Contact juridique" :true-value='1' :false-value='0' v-if='contactType == "client"'></q-checkbox>
          </q-card-section>
        </q-step>

        <q-step
          :name="4"
          title="Récapitulatif"
          icon="fas fa-user-check"
        >
          <business-contact v-model='contact'></business-contact>
          <q-inner-loading :showing="spinner">
              <q-spinner-gears size="7rem" color="primary"></q-spinner-gears>
          </q-inner-loading>
        </q-step>
      </template>

      <template v-if='contactType == "worker" || contactType == "exutoire"'>
        <q-step
          :name="2"
          title="Courriel"
          icon="fas fa-envelope"
          :done="step > 2"
        >
          <input-email @exists-already='existsAlready' @uniq='duplicateMail=false' :autofocus='true' v-model='contact.email' :unicity="true"></input-email>
        </q-step>

        <q-step
          :name="3"
          title="Informations diverses"
          icon="fas fa-address-card"
          :done="step > 3"
        >
          <input-title                   v-model="contact.civilite"></input-title>
          <input-line  label="Prénom"    v-model="contact.prenom"  ></input-line>
          <input-line  label="Nom"       v-model="contact.nom"     ></input-line>
          <input-phone label="Portable"  v-model="contact.portable"></input-phone>
        </q-step>

        <q-step
          :name="4"
          title="Récapitulatif"
          icon="fas fa-user-check"
        >
          <business-contact v-model='contact'></business-contact>
          <q-inner-loading :showing="spinner">
              <q-spinner-gears size="7rem" color="primary"></q-spinner-gears>
          </q-inner-loading>
        </q-step>
      </template>

      <template v-slot:navigation>
        <q-stepper-navigation class='fit row wrap justify-start items-start content-center'>
          <q-btn :disabled='spinner' @click="stepContinue(step)" color="primary" :label="step === 4 ? 'Créer' : 'Poursuivre'"></q-btn>
          <q-btn :disabled='spinner' v-if="step > 1" flat color="primary" @click="$refs.stepper.previous()" label="Revenir" class="q-ml-sm"></q-btn>
          <q-space></q-space>
          <q-btn :disabled='spinner' v-if='allowCancel' flat color="primary" @click="$emit('askedCancel')" label="Annuler" class="q-ml-sm"></q-btn>
        </q-stepper-navigation>
      </template>
      {{spinner}}
    </q-stepper>
    `
  },
  'contact': {
    props: {
      modelValue: {
        type: Object
      },
      viewType: {
        type: String,
        default: 'integrateur' // integrateur, clientlight, client
      }
    },
    emits: ['update:modelValue'],
    data() {
      return {
        primaryColor: Quasar.colors.getPaletteColor('primary'),
        dialogModel: []
      }
    },
    watch: {
    },
    mounted: function() {
      var vm = this
    },
    methods: {
      confirmation: function() {
        var vm = this
        Quasar.Dialog.create({
          title: 'Confirmation',
          message: 'Êtes-vous bien sûr de vouloir envoyer un mail de création de mot de passe ?',
          cancel: true,
          persistent: true,
          options: {
            model: vm.dialogModel,
            type: 'checkbox',
            items: [{label: 'obtenir le mot de passe temporaire', value: true}],
            inline: true,
            dense: true
          }
        }).onOk((option) => {
          var url = '/api/v1.0/integrateur/contact/'+vm.modelValue.id+'/sendpasswordcreation'
          if (option.length > 0)
            url += '/true'
          directPost(url, {}, function(data) {
            if (option.length > 0)
            Quasar.Notify.create({
              progress: true,
              message: 'Mot de passe : '+data.result,
              color: 'primary',
              position: 'center',
              actions: [
                { label: 'Copier dans presse-papier', color: 'yellow', handler: () => {
                  Quasar.copyToClipboard(data.result)
                  .then(() => {notify('positive', 'Données placées dans le presse-papier.')})
                  .catch(() => {notify('negative', 'Problème d\'accès au presse-papier.')})
                  }
                },
                { label: 'Fermer', color: 'white' }
              ]
            }, 5000)
            notify('positive', 'Mail envoyé')
          })
        }).onCancel(() => {
          notify('info', 'Action annulée')
        })
      }
    },
    template: `
    <q-card bordered>
      <div class="text-center" :style='{lineHeight: 0,fontSize: "6rem", marginTop: "0.5rem", color: (modelValue.civilite == 0 ? "#b9d667" : "#556080")}'><i class="far fa-user"></i></div>
      <q-card-section style='line-height: 0;'>
        <div class="text-subtitle1 text-center text-capitalize" :style='{color: primaryColor}'>{{modelValue.prenom}} {{modelValue.nom}}</div>
        <div class="text-subtitle1 text-center text-weight-thin text-italic">{{modelValue.poste}}&nbsp;</div>
        <div class="text-subtitle1 text-center text-weight-bold" :style='{color: primaryColor}' v-if='viewType != "clientlight"'>&nbsp;
          <span v-show='modelValue.contact_principal || modelValue.contact_juridique'>Contact&nbsp;</span>
          <span v-show='modelValue.contact_principal'>principal</span>
          <span v-show='modelValue.contact_principal && modelValue.contact_juridique'>&nbsp;et&nbsp;</span>
          <span v-show='modelValue.contact_juridique'>juridique</span>
          &nbsp;</div>
        <div class="text-subtitle1 text-center" style='font-style: oblique;font-size: smaller;'>
          &nbsp;
          <span v-show='!modelValue.actif && modelValue.invitation_envoyee == 1'>⚠ Le compte est désactivé ⚠</span>
          <span v-show='modelValue.invitation_envoyee == 0 && viewType != "clientlight"'>(simple fiche contact)</span>
          &nbsp;
        </div>
      </q-card-section>

      <q-separator inset></q-separator>

      <q-card-section>
        <input-email :readonly='true' v-model='modelValue.email'     v-if='modelValue.invitation_envoyee != 0'></input-email>
        <input-phone :readonly='true' v-model="modelValue.telephone" label="Téléphone"></input-phone>
        <input-phone :readonly='true' v-model="modelValue.portable"  label="Portable"></input-phone>
      </q-card-section>
      <template v-if='modelValue.actif && modelValue.invitation_envoyee == 1 && viewType == "integrateur"'>
        <q-separator inset></q-separator>
        <q-card-section>
          <q-btn color="primary" label="RAZ du Mot de Passe" style='width: 100%' @click='confirmation'></q-btn>
        </q-card-section>
      </template>
    </q-card>
    `
  },
  'contact-edit': {
    props: {
      modelValue: {
        type: Object
      },
      contactType: {
        type: String,
        default: 'user'
      },
      viewType: {
        type: String,
        default: 'integrateur'
      }
    },
    emits: ['update:modelValue', 'askedConversion', 'legalMainChecked', 'updatePassword'],
    data() {
      return {
        primaryColor: Quasar.colors.getPaletteColor('primary'),
        ref: 'ref-'+Math.floor(Math.random()*10000+10000),
        dialog: false,
        pswdDialog: false,
        newMail: '',
        duplicateMail: false,
        oldPassword: '',
        password: '',
        isOldPwd: true,
        isPwd: true,
        bar: null,
        passwordComplexEnough: false,
        errMsg: 'Nouveau mot de passe : au moins 8 charactères, avec des symboles, majuscule, chiffres.'
      }
    },
    watch: {
    },
    mounted: function() {
      var vm = this
    },
    methods: {
        testPassword: function(newVal, oldVal) {
          var vm = this
          result = zxcvbn(newVal, [this.modelValue.email, this.modelValue.email.split('@')])
          vm.passwordComplexEnough = result.score >= 3
          vm.bar.stop()
          vm.bar.animate(result.score/4);
          return vm.passwordComplexEnough
        },
        checkMail:function(mail) {
          return Utils.validators.email(mail)
        },
        validate: function() {
            var vm = this
            return vm.$refs[vm.ref].validate()
        },
        convert: function() {
            var vm = this
            if (vm.checkMail(vm.newMail) && !vm.duplicateMail) {
              vm.$emit('askedConversion', vm.modelValue.id, vm.newMail);
              vm.dialog = false
            }
            return vm.$refs[vm.ref].validate()
        },
        cancelConvert: function() {
            var vm = this
            vm.dialog = false
        },
        clickChangePassword: function() {
            var vm = this
            vm.pswdDialog = true
            if (vm.bar == null)
            vm.$nextTick(function() {
              vm.bar = new ProgressBar.Line('#passwdComplexity', {
                strokeWidth: 4,
                easing: 'easeInOut',
                duration: 250,
                color: '#FF0000',
                trailColor: '#eee',
                trailWidth: 1,
                svgStyle: {width: '100%', height: '100%'},
                from: {color: '#FF0000'},
                to: {color: '#5aed6a'},
                step: (state, bar) => {
                    bar.path.setAttribute('stroke', state.color);
                }
              });
            })
        },
        clickConvert: function() {
          var vm = this
          if (vm.modelValue.email.indexOf("random-ior-") != 0) {
            vm.$emit('askedConversion', vm.modelValue.id, vm.modelValue.email);
          }else
            vm.dialog=true
        }
    },
    template: `
    <q-form :ref='ref'>
        <input-title                   v-model="modelValue.civilite" ></input-title>
        <input-line  label="Prénom"    v-model="modelValue.prenom"   ></input-line>
        <input-line  label="Nom"       v-model="modelValue.nom"      ></input-line>
        <input-line  label="Poste"     v-model="modelValue.poste"    ></input-line>
        <input-email                   v-model="modelValue.email"    :unicity='true' :unicityexclude='modelValue.id' v-if='viewType == "integrateur" && (modelValue.invitation_envoyee != 0 || modelValue.email.indexOf("random-ior-") != 0)'></input-email>
        <input-email                   v-model="modelValue.email"    readonly v-if='viewType == "user"'></input-email>
        <input-phone label="Téléphone" v-model="modelValue.telephone"></input-phone>
        <input-phone label="Portable"  v-model="modelValue.portable" ></input-phone>
        <q-card>
          <q-card-section v-if='viewType == "integrateur"'>
            <q-checkbox v-model="modelValue.contact_principal" v-if='contactType == "client"' :disable='modelValue.contact_principal == 1' label="Contact principal" :true-value='1' :false-value='0'></q-checkbox>
            <q-checkbox v-model="modelValue.contact_juridique" v-if='contactType == "client"' :disable='modelValue.contact_juridique == 1' label="Contact juridique" :true-value='1' :false-value='0'></q-checkbox>
            <q-separator inset></q-separator>
            <q-checkbox v-if='modelValue.invitation_envoyee == 1' v-model="modelValue.actif" label="Compte actif" :true-value='1' :false-value='0'></q-checkbox>
            <q-btn v-if='modelValue.invitation_envoyee == 0' color='primary' @click="clickConvert">Transformer ce « contact simple » en « usager du site »</q-btn>
          </q-card-section>
        </q-card>
        <q-btn v-if='viewType == "user"' color='primary' @click="clickChangePassword">Changer le mot de passe</q-btn>
      <q-dialog v-model="dialog" persistent transition-show="scale" transition-hide="scale">
        <q-card  style="max-width: unset;max-height: unset; width: 20rem">
          <q-card-section>
            <div class="text-h6">Courriel du contact</div>
          </q-card-section>
          <q-card-section class="q-pt-none">
            <input-email @exists-already='duplicateMail=true' @uniq='duplicateMail=false' :autofocus='true' v-model='newMail' :unicity="true"></input-email>
          </q-card-section>
          <q-card-actions align="right">
          <q-btn label="Transformer" @click='convert'></q-btn>
          <q-btn label="Annuler"     @click='cancelConvert'></q-btn>
        </q-card-actions>
        </q-card>
      </q-dialog>
      <q-dialog v-model="pswdDialog" persistent transition-show="scale" transition-hide="scale">
        <q-card  style="max-width: unset;max-height: unset; width: 20rem">
          <q-card-section>
            <div class="text-h6">Changement du mot de passe</div>
          </q-card-section>
          <q-card-section class="q-pt-none">
            <q-input v-model="oldPassword" :type="isOldPwd ? 'password' : 'text'" label="Ancien mot de passe" hint='' :rules='[ val => val.length > 0 || ""  ]' bottom-slots>
              <template v-slot:prepend><q-icon name="fas fa-lock"></q-icon></template>
              <template v-slot:append>
                  <q-icon
                      :name="isOldPwd ? 'visibility_off' : 'visibility'"
                      class="cursor-pointer"
                      @click="isOldPwd = !isOldPwd"
                  ></q-icon>
                </template>
            </q-input>
            <q-input :disable='oldPassword.length == 0' v-model="password" :type="isPwd ? 'password' : 'text'"
                     label="Nouveau mot de passe" :rules='[ val => testPassword(val) || errMsg ]'
                     debounce="500" bottom-slots>
                <template v-slot:prepend><q-icon name="fas fa-lock"></q-icon></template>
                <template v-slot:append>
                  <q-icon
                      :name="isPwd ? 'visibility_off' : 'visibility'"
                      class="cursor-pointer"
                      @click="isPwd = !isPwd"
                  ></q-icon>
                </template>
                <template v-slot:label>
                    &nbsp;<div id="passwdComplexity"></div>
                </template>
            </q-input>
          </q-card-section>
          <q-card-actions align="right">
          <q-btn label="Mettre à jour le mot de passe" :disable='!passwordComplexEnough' @click="$emit('updatePassword', oldPassword, password)"></q-btn>
          <q-btn label="Annuler"                                                         @click='pswdDialog = false'></q-btn>
        </q-card-actions>
        </q-card>
      </q-dialog>
    </q-form>
    `
  },
  'look-create-load': {
    props: {
      modelValue: {
        type: String
      },
      entities: {
        type: Array
      },
      selectString: {
        type: String,
        default: 'Faites votre sélection'
      },
      createString: {
        type: String,
        default: 'Création'
      }
    },
    emits: ['update:modelValue', 'askedLoad', 'askedCreateEntity'],
    data() {
      return {
        entitySelected: null,
        newEntity: {
          raison_sociale: '',
          siret: '',
          adresse_administrative: '',
          formValidity: false,
          checking: false,
          patience: 'Vérification unicité ...'
        },
      }
    },
    watch: {
      entitySelected: function(newVal) {
        var vm = this
        if (newVal == null) return
        vm.$emit('askedLoad', newVal);
      },
    },
    mounted: function() {
      var vm = this
    },
    methods: {
      filterClient (val, update) {
        var vm = this
        if (val === '') {
          update(() => {
            vm.entitiesFiltered = vm.entities
          })
          return
        }
        update(() => {
          const needle = val.toLowerCase()
          vm.entitiesFiltered = vm.entities.filter(v => v.value.toLowerCase().indexOf(needle) > -1)
        })
      },
      createEntity: function(val) {
        var vm = this
        vm.newEntity.patience = 'Vérification unicité ...'
        vm.newEntity.checking = true
        post('/api/v1.0/integrateur/entreprise/siret/exists', {siret: vm.newEntity.siret}, function(data) {
          if (data.result.exists) {
            notify('warning', 'Ce SIRET est déjà utilisé.')
            vm.newEntity.formValidity = false
            vm.newEntity.checking = false
          }
          else {
            vm.$emit('askedCreateEntity', vm.newEntity)
          }
        }, function(data){
          notify('negative', 'Une erreur s\'est produite.<BR>Nous vous invitons essayer à nouveau et le cas échéant nous contacter.');
          vm.newEntity.checking = false
        })
      },
      manualClientIdSelect: function(client_id) {
        var vm = this
        var found = _.find(vm.entities, {value: client_id})
        if (found)
          vm.entitySelected = found.value
      }
    },
    template: `
    <q-tab-panels v-model="modelValue" animated style='width: 100%;'>
      <q-tab-panel name="choose">
        <div class="q-pa-md doc-container">
            <div class="column items-center">
              <input-select
                v-model='entitySelected'
                :options='entities'
                :label='selectString'
              ></input-select>
            </div>
            <slot name="advanced"></slot>
            <div class="column items-center">- ou -</div>
            <div class="column items-center">
              <q-card style='width: 100%;'>
                <q-form ref='createEntityForm' @submit="createEntity">
                  <q-card-section>
                    <div class="text-h6" style='text-align: center;'>{{createString}}</div>
                  </q-card-section>
                  <q-card-section>
                    <input-siret v-model='newEntity.siret' v-model:raisonsociale='newEntity.raison_sociale' v-model:adresse='newEntity.adresse_administrative' :required='true' :fullCheck='true'></input-siret>
                    <input-line  v-model='newEntity.raison_sociale' label='Raison sociale' :simplify='true'></input-line>
                    <div class="text-weight-light text-caption" v-if="newEntity.adresse_administrative">&nbsp;&nbsp;(Adresse associée à ce SIRET : {{newEntity.adresse_administrative}})</div>
                  </q-card-section>
                  <q-separator dark></q-separator>
                  <q-card-actions align="right">
                    <q-btn  type="submit" class='primary'>Créer</q-btn>
                  </q-card-actions>
                  <q-inner-loading :showing="newEntity.checking">
                    {{newEntity.patience}}
                    <q-spinner-gears style="width:5rem;height:5rem" color="primary">
                    </q-spinner-gears>
                  </q-inner-loading>
                </q-form>
              </q-card>
              ajout :<br>
              - de la possibilité d'ajouter un gestionnaire comme client, etc (et donc la protection pour éviter le même gestionnaire plusieurs fois comme client)<br>
              - de la possibilité d'ajouter un gestionnaire existant à un intégrateur (pls integrateur avec le même gestionnaire)<br>
              - RECHECK après modif !!!!!<br>
            </div>
        </div>
      </q-tab-panel>
      <q-tab-panel name="entityLoaded">
        <slot name="loaded"></slot>
      </q-tab-panel>
    </q-tab-panels>
    `
  },
  'societe': {
    props: {
      modelValue: {
        type: Object
      },
      readonly: {
        type: Boolean,
        default: false
      },
      type: {
        type: String,
        default: 'regulier'
      }
    },
    emits: ['update:modelValue', 'askedSave'],
    data() {
      return {
        jsonBackup: '',
        ref: 'ref-societe-'+Math.floor(Math.random()*10000+10000),
      }
    },
    watch: {
      modelValue: function(newVal) {
        var vm = this
        vm.jsonBackup = JSON.stringify(newVal)
      },
    },
    mounted: function() {
      var vm = this
      vm.jsonBackup = JSON.stringify(vm.modelValue)
    },
    methods: {
      validate: function() {
        var vm = this
        return vm.$refs[vm.ref].validate()
      },
      changed: function() {
        var vm = this
        return ! vm.$refs[vm.ref+'ghostwrapper'].identical
      },
      submit: function(newVal) {
        var vm = this
        vm.$refs[vm.ref].validate().then(success => {
          if (success) {
            var params = Utils.misc.diff(JSON.stringify(newVal), JSON.stringify(vm.modelValue), function (client) {//clientFlatten
              client = _.merge(client, client.entreprise)
              delete client['entreprise'];
              return client
            })
            if (Object.keys(params).length == 0) {notify('notice', 'Aucune modification à enregistrer.');return}
            vm.$emit('askedSave', params);
          }else{
            notify('notice', 'Veuillez compléter le formulaire avant de le sauver.')
          }
        })
      },
      reset: function() {
        var vm = this
        if (vm.jsonBackup == '') return
        vm.$emit('update:modelValue', JSON.parse(vm.jsonBackup));
      },
      synchronize: function() {
        var vm = this
        vm.jsonBackup = JSON.stringify(vm.modelValue)
      }
    },
    template: `
    <q-form :ref='ref' @submit="submit" @reset='reset' style='width: 100%;' v-if='modelValue!= null'>
      <ghost-wrapper :ref='ref+"ghostwrapper"' v-model:one='modelValue'>
        <template v-slot='props'>
          <q-card flat style='width: 100%;' v-if='props.clones.one != null'>
            <q-card-section>
              <input-line  label="Raison sociale"    v-model="props.clones.one.entreprise.raison_sociale"         :readonly='readonly' :simplify='true' :required='true'></input-line>
              <input-siret                           v-model="props.clones.one.entreprise.siret"                  :readonly='readonly'                  :required='true'></input-siret>
              <input-address                         v-model="props.clones.one.entreprise.adresse_administrative" :readonly='readonly'                  :required='true'></input-address>
              <input-date  label="Date contrat"      v-model="props.clones.one.contrat"                           :readonly='readonly'                  :required='true' v-if='modelValue.contrat != undefined'></input-date>
              <input-email label='Mail de contact'   v-model="props.clones.one.email"                             :readonly='readonly'                  :required='true'
                          v-if='type == "client" || type == "exutoire"'
              ></input-email>
              <input-phone label="Téléphone"         v-model="props.clones.one.telephone"                         :readonly='readonly'                  :required='true'
                          v-if='type == "client" || type == "exutoire"'
              ></input-phone>
              <input-line  label="Prénom et nom du contact figurant sur le BSD"    v-model="props.clones.one.contact"         :readonly='readonly' :simplify='true' :required='true'
                          v-if='type == "exutoire"' icon='fas fa-user'
              ></input-line>
            </q-card-section>
            <q-card-actions v-if='!readonly' class="q-px-md">
                <q-btn :disable='props.identical' label="Enregistrer"   @click='submit(props.clones.one)'></q-btn>
                <q-btn :disable='props.identical' label="Réinitialiser" @click='props.reset()'></q-btn>
            </q-card-actions>
          </q-card>
        </template>
      </ghost-wrapper>
    </q-form>
    `
  },
  'select': {
    props: {
      modelValue: {
        type: [Array, Number]
      },
      selected: {
        type: Object
      },
      options: {
        type: [Array, String, Function],
        default: []
      },
      multiple: {
        type: Boolean,
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
      selectFirst: {
        type: Boolean,
        default: false
      },
      label: {
        type: String,
        default: 'Veuillez sélectionner'
      },
      clearable: {
        type: Boolean,
        default: false
      },
      readonly: {
        type: Boolean,
        default: false
      },
      subclass: {
        type: String,
        default: 'form-input col-grow q-field--with-bottom'
      },
    },
    emits: ['update:modelValue', 'update:selected'],
    data() {
      return {
        internalOptions: [],
        internalModel: null,
        loadingOptions: false
      }
    },
    watch: {
      modelValue: function(newVal) {
        var vm = this
        vm.loadModelValue()
        //vm.internalModel = newVal
      },
      internalModel: function(newVal) {
        var vm = this
        if (newVal == vm.modelValue) return
        if (newVal == null) {
          vm.$emit('update:modelValue', null)
          vm.$emit('update:selected', null)
          return
        }
        if (vm.multiple == false) {
          vm.$emit('update:modelValue', newVal[vm.optionValue])
          vm.$emit('update:selected', newVal)
        }
        else{
          var tmp = []
          newVal.forEach(function(val, idx, arr){
            tmp.push(val[vm.optionValue])
          })
          vm.$emit('update:modelValue', tmp)
          vm.$emit('update:selected', newVal)
        }
      },
      options: function(newVal) {
        var vm = this
        vm.optionsChanged(newVal)
      },
    },
    mounted: function() {
      var vm = this
      vm.optionsChanged(vm.options)
    },
    methods: {
      loadModelValue: function() {
        var vm = this
        if (vm.modelValue == null || vm.modelValue == undefined) {
          vm.internalValue = vm.multiple ? [] : null
          return
        }
        if (vm.loadingOptions) return
        if (vm.multiple == false) {
          vm.internalValue = _.find(vm.options, function(o) { return o.value == vm.modelValue; });
          return
        }
        var tmpModel = []
        if (vm.internalValue == null || !_.isEqual(vm.modelValue, vm.internalValue)) {
          var tmp = null
          vm.modelValue.forEach(function(val){
            tmp = _.find(vm.options, function(o) { return o[vm.optionValue] == val; });
            if (tmp != null)
              tmpModel.push(tmp)
          })
          vm.internalValue = tmpModel;
          return
        }
      },
      optionsChanged: function(newValue) {
        var vm = this
        if (newValue === '' || newValue === null || newValue === undefined) {
          vm.internalOptions = []
          vm.loadModelValue()
          return
        }
        if (Array.isArray(newValue)) {
          vm.internalOptions = vm.options
          if(vm.selectFirst && vm.options.length > 0 && [null, undefined, ''].indexOf(vm.modelValue) >= 0)
            vm.internalModel = vm.options[0]
          else if (vm.options.length > 0)
            vm.loadModelValue()

          return
        }
        if (typeof newValue == 'string') {
          post(newValue, {}, function(data) {
            if (data.status == true) {
              vm.internalOptions = data.result
              if(vm.selectFirst && vm.internalOptions.length > 0 && [null, undefined, ''].indexOf(vm.modelValue) >= 0)
                vm.internalModel = vm.internalOptions[0]
              else if (vm.options.length > 0)
                vm.loadModelValue()

              return
            }
            notify('negative', 'Une erreur s\'est produite.<BR>Nous vous invitons essayer à nouveau et le cas échéant nous contacter.');
          })
          return
        }
        console.debug('business-select ; je ne sais pas quoi faire de ce type de « options »')
      }
    },
    template: `
    <q-select filled outlined :class='subclass'
      v-model="internalModel"
      :multiple='multiple'
      :options="internalOptions"
      :option-value="optionValue"
      :option-label="optionLabel"
      :use-chips='multiple'
      :clearable='clearable'
      stack-label
      :label="label"
    ></q-select>
    `
  },
  'rubrique-dechet': {
    props: {
      modelValue: {
        type: String
      },
      label: {
        type: String,
        default: 'Rubrique'
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
        dialog: false,
        showCopyToClipboard: false,
        rubrique: [],
        code_filtered: [],
      }
    },
    watch: {
      modelValue: function(newVal, oldVal) {
        var vm = this
        if (newVal.trim() == '' && newVal != oldVal) {
          vm.code_filtered = _.cloneDeep(vm.rubrique)
        }

        var toLook = newVal.toLocaleLowerCase()
        var toLookStripped = newVal.replace(/[-_\s]/g, '')
        var clone = _.cloneDeep(vm.rubrique)
        var keep = function (node) {
          if (node == undefined) return true

          if (node.valueStripped.indexOf(toLookStripped) != -1) return true
          if (node.description.indexOf(toLook) != -1) return true
          if (node.children == undefined) return false
          for(var i = node.children.length -1 ; i >= 0 ; i--) {
            if (!keep(node.children[i]))
              node.children.splice(i, 1)
          }
          return node.children.length > 0
        }
        root = {value: '', description: '', valueStripped:'', children: clone}
        keep(root)
        vm.code_filtered = root.children
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
      axios.get('/data/code_dechet.js')
      .then(function (response) {
        vm.rubrique = eval(response.data)
        vm.code_filtered = vm.rubrique
      })
      .catch(function (errorObj) {
          console.debug(errorObj)
          notify('negative', 'Une erreur s\'est produite.<BR>Nous vous invitons essayer à nouveau et le cas échéant nous contacter.');
      })
    },
    methods: {
      copyToClipboard: function(text) {
        Quasar.copyToClipboard(text)
        .then(() => {notify('positive', 'Données placées dans le presse-papier.')})
        .catch(() => {notify('negative', 'Problème d\'accès au presse-papier.')})
      },
      inputClicked: function() {
        var vm = this
        if (vm.readonly == false)
          vm.dialog=true
      },
      codeSelected: function(item) {
        var vm = this
        vm.item = item
        vm.$emit('update:modelValue', item.value)
        vm.dialog = false
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
        return (!vm.required && val == '') || val.length > 0 || 'Le code est invalide'
      },
      isIn: function(val) {
        var vm = this
        if (vm.modelValue == undefined || vm.modelValue.trim() == '') return true
        return _.find(vm.code_filtered, function(o) {
          return o.value.indexOf(val) == 0
        })
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
      <q-dialog v-model="dialog" persistent>
        <q-card style='width: 40rem;'>
          <q-card-section>
            <div class="text-h6">Codes correspondants</div>
          </q-card-section>

          <q-card-section class="q-pt-none">
            <div class="q-pa-md fit row wrap justify-center items-start content-start">
              <div class="row items-start" style='width: 100%;'>
                <q-input debounce='500' filled outlined  class='form-input' label='Recherche'
                        :model-value="modelValue" @update:model-value="value => $emit('update:modelValue', value)"
                ></q-input>
              </div>
              <div class="row items-start" style='width: 100%;'>
              Codes correspondants :
              </div>
              <div class="row items-start" style='width: 100%;'>
                <div style="width: 100%">
                    <q-tree
                      :nodes="code_filtered"
                      node-key="label" style='overflow-y: auto;height: 40vh;'
                    >
                      <template v-slot:default-header="prop">
                        <div style='cursor: pointer'>
                          <div class="row items-center" v-if='prop.node.level==1 && isIn(prop.node.value)'>
                            <div class="text-weight-bold text-uppercase text-primary">{{ prop.node.label }} - {{ prop.node.description }}</div>
                          </div>
                          <div class="row items-center" v-if='prop.node.level==2'>
                            <div class="text-secondary">{{ prop.node.label }} - {{ prop.node.description }}</div>
                            <div>{{ prop.node.comment }}</div>
                          </div>
                          <div class="row items-center" v-if='prop.node.level==3'  @click='codeSelected(prop.node)'>
                            <div>{{ prop.node.label }} - {{ prop.node.description }}</div>
                          </div>
                        </div>
                      </template>
                    </q-tree>
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
  'code-traitement': {
    props: {
      modelValue: {
        type: String
      },
      label: {
        type: String,
        default: 'Code traitement'
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
        dialog: false,
        showCopyToClipboard: false,
        code_dechet: [],
        code_filtered: [],
      }
    },
    watch: {
      modelValue: function(newVal, oldVal) {
        var vm = this
        if (newVal.trim() == '' && newVal != oldVal) {
          vm.code_filtered = _.cloneDeep(vm.code_dechet)
        }

        var toLook = newVal.toLocaleLowerCase()
        var toLookStripped = newVal.toLocaleLowerCase().replace(/[-_\s]/g, '')
        var clone = _.cloneDeep(vm.code_dechet)
        var keep = function (node) {
          if (node == undefined) return true
          if (node.valueStripped.toLocaleLowerCase().indexOf(toLookStripped) != -1) return true
          if (node.description.toLocaleLowerCase().indexOf(toLook) != -1) return true
          if (node.comment != undefined && node.comment.toLocaleLowerCase().indexOf(toLook) != -1) return true
          if (node.children == undefined) return false
          for(var i = node.children.length -1 ; i >= 0 ; i--) {
            if (!keep(node.children[i]))
              node.children.splice(i, 1)
          }
          return node.children.length > 0
        }
        root = {value: '', description: '', comment: '', valueStripped:'', children: clone}
        keep(root)
        vm.code_filtered = root.children
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
      axios.get('/data/code_traitement.js')
      .then(function (response) {
        vm.code_dechet = eval(response.data)
        vm.code_filtered = vm.code_dechet
      })
      .catch(function (errorObj) {
          console.debug(errorObj)
          notify('negative', 'Une erreur s\'est produite.<BR>Nous vous invitons essayer à nouveau et le cas échéant nous contacter.');
      })
    },
    methods: {
      copyToClipboard: function(text) {
        Quasar.copyToClipboard(text)
        .then(() => {notify('positive', 'Données placées dans le presse-papier.')})
        .catch(() => {notify('negative', 'Problème d\'accès au presse-papier.')})
      },
      inputClicked: function() {
        var vm = this
        if (vm.readonly == false)
          vm.dialog=true
      },
      codeSelected: function(item) {
        var vm = this
        vm.item = item
        vm.$emit('update:modelValue', item.value)
        vm.dialog = false
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
        return (!vm.required && val == '') || val.length > 0 || 'Le code est invalide'
      },
      isIn: function(val) {
        var vm = this
        if (vm.modelValue.trim() == '') return true
        return _.find(vm.code_filtered, function(o) {
          return o.value.indexOf(val) == 0
        })
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
      <q-dialog v-model="dialog" persistent>
        <q-card style='width: 40rem;'>
          <q-card-section>
            <div class="text-h6">Codes correspondants</div>
          </q-card-section>

          <q-card-section class="q-pt-none">
            <div class="q-pa-md fit row wrap justify-center items-start content-start">
              <div class="row items-start" style='width: 100%;'>
                <q-input debounce='500' filled outlined  class='form-input' label='Recherche'
                        :model-value="modelValue" @update:model-value="value => $emit('update:modelValue', value)"
                ></q-input>
              </div>
              <div class="row items-start" style='width: 100%;'>
              Codes correspondants :
              </div>
              <div class="row items-start" style='width: 100%;'>
                <div style="width: 100%">
                    <q-tree
                      :nodes="code_filtered"
                      node-key="label" style='overflow-y: auto;height: 40vh;'
                    >
                      <template v-slot:default-header="prop">
                        <div style='cursor: pointer'>
                          <div class="full-width column wrap justify-start items-start content-start" v-if='prop.node.level==1 && isIn(prop.node.value)'>
                            <div class="text-weight-bold text-uppercase text-primary">{{ prop.node.label }} - {{ prop.node.description }}</div>
                          </div>
                          <div class="full-width column wrap justify-start items-start content-start" v-if='prop.node.level==2'  @click='codeSelected(prop.node)'>
                            <div class="text-secondary">{{ prop.node.label }} - {{ prop.node.description }}</div>
                            <div class="text-italic" style='font-size: smaller;' v-if='prop.node.comment.length > 0'>{{ prop.node.comment }}</div>
                          </div>
                        </div>
                      </template>
                    </q-tree>
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
  'attestation-type': {
    props: {
      modelValue: {
        type: [Number]
      },
      options: {
        type: [Array, String, Function],
        default: [{value: 0, label: 'aucun parmi les 5 flux'}, {value:1, label: 'papier/carton'}, {value:2, label: 'métal'}, {value: 3, label: 'plastique'}
                , {value: 4, label: 'verre'}, {value: 5, label: 'bois'}]
      },
      selectFirst: {
        type: Boolean,
        default: false
      },
      label: {
        type: String,
        default: 'Type de flux'
      },
      clearable: {
        type: Boolean,
        default: false
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
        default: ''
      },
    },
    emits: ['update:modelValue'],
    data() {
      return {
        internalOptions: [],
      }
    },
    watch: {
    },
    mounted: function() {
      var vm = this
    },
    methods: {
    },
    template: `
    <input-select :class='subclass'
      :model-value="modelValue" @update:model-value="value => $emit('update:modelValue', value)"
      :options="options"
      :clearable='clearable'
      :use-input='false'
      :label="label"
      :readonly='readonly'
      :subclass='subclass'
      :substyle='substyle'
    ></input-select>
    `
  },
  'dechet-edit': {
    props: {
      modelValue: {
        type: Object
      },
    },
    emits: ['update:modelValue'],
    data() {
      return {
        ref: 'ref-dechet-edit-'+Math.floor(Math.random()*10000+10000),
        affiches: [
          {text: "tri"          , value:"affiche_tri"          , url: 'sortPoster'},
          {text: "communication", value:"affiche_communication", url: 'commPoster'},
          {text: "valorisation" , value:"affiche_valorisation" , url: 'valorisationPoster'}
        ],
        affiche: {text: "tri", value:"affiche_tri", url: 'sortPoster'},
      }
    },
    watch: {
    },
    computed: {
      img: function() {
        var vm = this
        return vm.modelValue.photo == '' ? '/image/no-camera-sign.svg' : '/dechet/'+vm.modelValue.photo
      }
    },
    mounted: function() {
      var vm = this
      vm.affiche = vm.affiches[0]
    },
    methods: {
      documentDownload: function() {
        var vm = this
        window.open('/api/v1.0/integrateur/dechet/'+vm.modelValue.id+'/'+vm.affiche.url+'/download');
      },
      documentUpload: function() {
        var vm = this
      },
    },
    template: `
    <q-form :ref='ref'>
      <q-card style='width: 100%'>
        <q-card-section horizontal>
          <div class='column wrap justify-start items-start content-start col-5 q-card__section--vert'>
            <q-uploader
                label="Image"
                :hide-upload-btn='true'
                accept='image/svg+xml'
                style='width: 100%;max-height: unset;'
                class='form-input q-field--with-bottom'
                @added='files => modelValue.file = files[0]'
                @removed='modelValue.file = null'
            >
              <template v-slot:list="scope">
                <q-img v-if='scope.files.length == 0'
                  :src="img"
                  :ratio="1"
                  fit='contain'
                  @click='addDechetImage=true'
                >
                </q-img>
                <q-img v-if='scope.files.length > 0'
                  :src="scope.files[0].__img.src"
                  :ratio="1"
                  fit='contain'
                  @click='addDechetImage=true'
                >
                  <div class="full-width row wrap justify-start items-start content-start" style='margin: 0;padding: 0;'>
                    <q-space></q-space>
                    <q-btn flat rounded dense @click="scope.removeFile(scope.files[0])"  icon="clear"></q-btn>
                  </div>
                </q-img>
              </template>
            </q-uploader>
            <q-space></q-space>
          </div>
          <q-separator :vertical='true'></q-separator>
          <q-card-section class='col-7'>
            <div class='full-width row wrap justify-start items-start content-start'>
              <input-line label='Trigramme' v-model='modelValue.trigramme' substyle='max-width: 7rem;' :maxlength='3' required></input-line>
              <input-line label='Nom'       v-model='modelValue.nom'       substyle='width:unset'      required></input-line>
            </div>
            <q-space class='q-field--with-bottom'></q-space>
            <div class="full-width row wrap justify-start items-start content-start">
              <input-number label="Ordre d'affichage" v-model='modelValue.ordre_affichage' :min='0' :max='999' :step='1'></input-number>
              <input-color v-model='modelValue.couleur' substyle="width: unset;"></input-color>
            </div>
            <business-attestation-type v-model='modelValue.attestation_type'></business-attestation-type>
            <q-card style="margin-bottom: 1em;">
              <q-item>
                <q-item-label caption>Codes BSD</q-item-label>
              </q-item>
              <q-card-section class='full-width row wrap justify-start items-start content-start' style='padding-top: 0;padding-bottom: 0;'>
                <business-rubrique-dechet      v-model='modelValue.rubrique'     substyle='max-width: 50%;'></business-rubrique-dechet>
                <business-code-traitement  v-model='modelValue.code_traitement' substyle='max-width: 50%;'></business-code-traitement>
              </q-card-section>
            </q-card>
            <q-card style="margin-bottom: 1em;">
              <q-item>
                <q-item-label caption>Équivalents environnementaux</q-item-label>
              </q-item>
              <q-card-section class='fit row wrap justify-start items-center content-center' style='padding-top: 0;'>
                1 kg de « {{modelValue.nom}} » équivaut à&nbsp;
                <!--input-number labell="coefficient"  v-model='modelValue.equivalence_coefficient' :min='0' classs='col-6'></input-number-->
                <q-input filled v-model='modelValue.equivalence_coefficient' type="number" :min='0' :step='0.000001' style='width: 8em;'></q-input>
                <q-input filled v-model='modelValue.equivalence_nom'  style='flex: 2 0 auto;'      required></q-input>
              </q-card-section>
              <q-card-section class='fit row wrap justify-start items-center content-center' style='padding-top: 0;'>
              <fieldset>
                <legend>Illustration</legend>
                <q-img
                  :src="'/dechet/'+modelValue.equivalence_photo"
                  style="width: 10em;max-height: 10em;"
                  fit="contain"
                >
                </q-img>
              </fieldset>
              <q-uploader
                  label="Mise à jour de l'illustration"
                  :hide-upload-btn='true'
                  accept='image/webp'
                  class='form-input q-field--with-bottom'
                  field-name="equivalence_photo-webp"
                  @added="files => modelValue['equivalence_photo-webp'] = files[0]"
                  @removed="modelValue['equivalence_photo-webp'] = null"
                  no-thumbnails
                  style="height: 5em;width: unset;margin-left: 1em;"
              >
                <template v-slot:list="scope"><div style='height: 0px'></div></template>
              </q-uploader>
              </q-card-section>
            </q-card>
            <q-card style="margin-bottom: 1em;">
              <q-item>
                <q-item-label caption>Affiches de communication</q-item-label>
              </q-item>
              <q-card-section class='fit row wrap justify-start items-center content-center' style='padding-top: 0;'>
                <q-select filled v-model="affiche" :options="affiches" map-options option-value="value" option-label="text" label="Document" style="width: 15em;margin-right: 1em;"></q-select>
                <q-btn size="sm" icon="fas fa-download" :disable="modelValue[affiche.value] == ''" @click='documentDownload'>
                  <q-tooltip>Téléchargement du document</q-tooltip>
                </q-btn>
                &nbsp;
                <q-uploader
                    label="Mise à jour du document"
                    :hide-upload-btn='true'
                    accept='application/pdf'
                    class='form-input q-field--with-bottom'
                    :field-name="affiche.value+'-pdf'"
                    @added="files => modelValue[affiche.value+'-pdf'] = files[0]"
                    @removed="modelValue[affiche.value+'-pdf'] = null"
                    no-thumbnails
                    style="height: 5em;width: unset;margin-left: 1em;"
                >
                  <template v-slot:list="scope"><div style='height: 0px'></div></template>
                </q-uploader>
              </q-card-section>
            </q-card>
          </q-card-section>
        </q-card-section>
        <slot name="bottom"></slot>
      </q-card>
    </q-form>
    `
  },
  'pointcollecte': {
    props: {
      modelValue: {
        type: Object
      },
      type: {
        type: String,
        default: 'regulier'
      }
    },
    emits: [],
    data() {
      return {
        jsonBackup: '',
        ref: 'ref-pointcollecte-'+Math.floor(Math.random()*10000+10000),
      }
    },
    watch: {
    },
    mounted: function() {
      var vm = this
    },
    methods: {
    },
    template: `
      <q-card flat style='width: 100%;'>
        <q-card-section>
          <input-line      v-model='modelValue.nom'               readonly label='Nom du point de collecte'></input-line>
          <input-address   v-model='modelValue.adresse'           readonly                                 ></input-address>
          <input-point     v-model='modelValue.coordonnees'       readonly label='Coordonnées GPS'         ></input-point>
          <input-phone     v-model='modelValue.telephone'         readonly                                 ></input-phone>
          <input-multiline v-model='modelValue.ascenseur'         readonly label='Ascenceur' :maxlength='4096'></input-multiline>
          <input-multiline v-model='modelValue.parking'           readonly label='Parking' :maxlength='4096'></input-multiline>
          <input-multiline v-model='modelValue.badge_acces'       readonly label='Badge accès' :maxlength='4096'></input-multiline>
          <input-multiline v-model='modelValue.hauteur'           readonly label='Hauteur' :maxlength='4096'></input-multiline>
          <input-multiline v-model='modelValue.batiment'          readonly label='Bâtiment' :maxlength='4096'></input-multiline>
          <input-multiline v-model='modelValue.code_acces'        readonly label='Code accès' :maxlength='4096'></input-multiline>
          <input-multiline v-model='modelValue.creneaux'          readonly label='Créneaux' :maxlength='4096'></input-multiline>
          <q-checkbox      v-model='modelValue.producteur_dechet' disable  label='Producteur du déchet' :left-label='true' :true-value='1' :false-value='0'></q-checkbox>
          <input-multiline v-model='modelValue.commentaire'       readonly label='Autre' :maxlength='4096'></input-multiline>
        </q-card-section>
      </q-card>
    `
  },
}

VueBusinessPageComponents= {
  'collecteurs_clients_clients_sites-selecteur_gestionnaire': {
    props: {
      modelValue: {
        type: [Number]
      },
      subclass: {
        type: String,
        default: ''
      },
    },
    emits: ['update:modelValue'],
    data() {
      return {
        internalModel: null,
        options: [],
        gestionnaire: null,
        loading: true
      }
    },
    watch: {
      modelValue: function(newVal) {
        var vm = this
        if (vm.loading) return
        vm.loadModelValue()
      },
      internalModel: function(newVal) {
        var vm = this
        if (vm.loading) return
        if (newVal == null) {
          vm.$emit('update:modelValue', null)
          return
        }
        vm.loadGestionnaire()
        if (newVal != vm.modelValue) {
          vm.$emit('update:modelValue', newVal.value)
          return
        }
      },
    },
    mounted: function() {
      var vm = this
      vm.loadGestionnaires()
    },
    methods: {
      loadModelValue: function() {
        var vm = this
        if (vm.modelValue == null) {
          vm.internalModel = null
          vm.gestionnaire = null
        }
        vm.internalModel = _.find(vm.options, function(o) { return o.value == vm.modelValue; });
      },
      loadGestionnaires: function() {
        var vm = this
        post('/api/v1.0/integrateur/gestionnaire/list', {}, function(data) {
          vm.options = _.reduce(data.result.gestionnaires, function(acc, val) {
            acc.push({label: val.raison_sociale, value: val.id})
            return acc
          }, [])

          vm.$nextTick(function() {
            vm.loading = false
            vm.loadModelValue()
          })
        })
      },
      loadGestionnaire: function() {
        var vm = this
        if (vm.internalModel == null) return
        post('/api/v1.0/integrateur/gestionnaire/'+vm.internalModel.value, {}, function(data) {
          vm.gestionnaire = data.result
        })
      },
    },
    template: `
    <q-card class='col-grow'>
      <q-card-section>
        <q-select clearable label="Sélection d'un gestionnaire" :options='options' v-model='internalModel'></q-select>
      </q-card-section>
      <q-card-section>
        <business-societe v-if='gestionnaire != null' :readonly='true' v-model='gestionnaire'></business-societe>
      </q-card-section>
    </q-card>
    `
  },

  'collecteurs_clients_clients_sites-selecteur_gestionnaire_contact': {
    props: {
      modelValue: {
        type: [Number]
      },
      gestionnaire: {
        type: [Number]
      },
      subclass: {
        type: String,
        default: ''
      },
    },
    emits: ['update:modelValue'],
    data() {
      return {
        internalModel: null,
        options: [],
        contact: null,
        initialLoad: true
      }
    },
    watch: {
      modelValue: function(newVal) {
        var vm = this
        vm.loadModelValue()
      },
      internalModel: function(newVal) {
        var vm = this
        if (newVal == null) {
          vm.$emit('update:modelValue', null)
          return
        }
        if (newVal.value != vm.modelValue)
          vm.$emit('update:modelValue', newVal.value)
        vm.loadGestionnaireContact(newVal.value)
      },
      gestionnaire: function(newVal) {
        var vm = this
        vm.internalModel = null
        if (newVal == null) {
          vm.options = []
          return
        }
        vm.loadGestionnaireContacts()
      },
    },
    mounted: function() {
      var vm = this
      vm.loadGestionnaireContacts()
    },
    methods: {
      loadModelValue: function() {
        var vm = this
        if (vm.modelValue == null || vm.modelValue == undefined) {
          vm.internalModel = null
          vm.contact       = null
          return
        }
        if (vm.internalModel && vm.internalModel.value == vm.modelValue) return
        vm.internalModel = _.find(vm.options, function(o) { return o.value == vm.modelValue; });
      },
      loadGestionnaireContact: function(contact_id) {
        var vm = this
        post('/api/v1.0/integrateur/gestionnaire/'+vm.gestionnaire+'/contact/'+contact_id, {}, function(data) {
          vm.contact = data.result
        })
      },
      loadGestionnaireContacts: function() {
        var vm = this
        if (vm.gestionnaire == null) {
          vm.options = []
          return
        }
        post('/api/v1.0/integrateur/gestionnaire/'+vm.gestionnaire+'/contact/list', {}, function(data) {
          vm.options = _.reduce(data.result, function(acc, val) {
            if (val.email.indexOf('random-ior-') == 0)
              acc.push({label: val.prenom+' '+val.nom, value: val.id})
            else
              acc.push({label: val.prenom+' '+val.nom+ '('+val.email+')', value: val.id})
            return acc
          }, [])
          vm.loadModelValue()
        })
      },

    },
    template: `
    <q-card class='col-grow'>
      <q-card-section>
        <q-select clearable label="Sélection d'un contact" v-model='internalModel' :options='options'></q-select>
      </q-card-section>
      <q-card-section>
        <business-contact v-if='contact != null' v-model='contact'></business-contact>
      </q-card-section>
    </q-card>
    `
  },

}
