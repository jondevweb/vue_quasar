<template>
  <div id='q-app'>
  <q-layout view="lHh Lpr lFf" v-for="data in datas" :key="data.title">
    <!-- <HeaderSite :title="data"  /> -->
    <q-header elevated style='height: 4em;'>
      <q-toolbar class='fit row wrap justify-start items-start content-center' style='background: white; padding-left: 0;'>
        <q-btn
            flat
            dense
            round
            @click="toggleLeftDrawer"
            aria-label="Menu"
            icon="menu"
            class="q-mr-sm"
            v-show='!panelVisible'
            style='color: #003F6E'
        ></q-btn>
        <div class='logowrapper'>
            <q-img
                src="../assets/ID-icionrecycle-logo.svg"
                spinner-color="white"
                style="max-width: 150px"
                mode='fit'
                @click='reload'
                class='cursor-pointer'
            ></q-img>
        </div>
        <q-space></q-space>
        <!-- <q-select
            v-if='visible'
        > -->
        <q-select
            filled
            v-model="datas[0].account[0].pointcollectes[0].nom"
            use-input
            input-debounce="0"
            label="Point de collecte"
            :options="options"
            @filter="filterFn"
            behavior="menu"
            style='background-color: #e5f4be;'
            option-label='nom'
        >
          <template v-slot:no-option>
          <q-item>
              <q-item-section class="text-grey">
              Aucun site de trouvé
              </q-item-section>
          </q-item>
          </template>
        </q-select>
        <q-space></q-space>
        <div class="q-gutter-sm row items-center no-wrap">
          <q-btn round dense flat>
              <q-img src="../assets/email.svg" spinner-color="white" mode='fit' @click='headBar.demandeCollecte.dialog = true'></q-img>
          </q-btn>
          <q-btn round dense flat v-if='showAll'>
              <q-img src="../assets/email.svg" spinner-color="white" mode='fit'></q-img>
              <q-tooltip>Messages</q-tooltip>
          </q-btn>
          <q-btn round dense flat icon="notifications" class='notificationicon' v-if='showAll'>
              <q-badge color="red" text-color="white" floating>2</q-badge>
              <q-tooltip>Notifications</q-tooltip>
          </q-btn>
          <q-btn-dropdown round flat color="primary" icon='img:../src/assets/user.svg'>        
              <template v-slot:label>
                  <div class="row items-center no-wrap text-center" style='font-weight: normal;font-size: smaller;line-height: normal; color: #003F6E' v-bind="data">
                      {{ data.account[0].prenom }}
                      <br>
                      {{ data.account[0].nom }}
                  </div>
              </template> 
              <q-list>
                  <q-item clickable v-close-popup @click="account.dialog = true">
                  <q-item-section>
                      <q-item-label>Mes informations</q-item-label>
                  </q-item-section>
                  </q-item>
                  <q-separator></q-separator>
                  <q-item clickable v-close-popup href='https://triethic.fr/wp-content/uploads/2022/02/Ici-on-recycle.pdf'>
                  <q-item-section>
                      <q-item-label>Documentation</q-item-label>
                  </q-item-section>
                  </q-item>
                  <q-separator></q-separator>
                  <q-item clickable v-close-popup @click="logout">
                  <q-item-section>
                      <q-item-label>Déconnexion</q-item-label>
                  </q-item-section>
                  </q-item>
              </q-list>
          </q-btn-dropdown>
        </div>
      </q-toolbar>
    </q-header>
    <!-- <MenuPrincipal :title="data"  /> -->
    <q-drawer v-model="leftDrawerOpen"
    id='leftpanel'>
      <q-scroll-area class="fit">
          <q-list class="rounded-borders" v-for="data in data.leftMenu" v-bind:key="data.title">
            <q-expansion-item v-if='data.children != undefined && (!data.hidded || showAll)'
                          v-bind:icon='data.icon'
                          v-bind:label='data.title'
                          v-bind:header-inset-level='0'
                          v-bind:content-inset-level='1'
                          :class="{'short-inset': level == 1}"
                          v-model='data.opened'            
                      >
              <q-item style='width: 107%;' clickable v-ripple v-for="child in data.children" :key="child.title" @click="toggleLeftDrawer" >
                <q-item-section @click="component = child.path ">
                  {{child.title}}
                  <q-expansion-item v-if='child.children != undefined && (!data.hidded || showAll)'>
                    <q-item clickable v-for="children in child.children" :key="children.title">
                        <q-item-section>{{children.title}}</q-item-section>
                    </q-item> 
                  </q-expansion-item> 
                </q-item-section> 
              </q-item>     
            </q-expansion-item>                       
            <q-item clickable v-ripple v-if='data.children == undefined && (!data.hidded || showAll)'
                active-class='active-menu-link'
                v-bind:active='data.selected'
                @click="$emit('selected', data)"
            >
              <q-item-section v-if='data.icon != undefined  && (!data.hidded || showAll)' avatar>
                  <q-icon v-bind:name='data.icon' :style='data.style' :color='data.selected ? "#b1dc4c" : "white"'></q-icon>
              </q-item-section>
              <q-item-section :style='{color: (data.selected ? "#b1dc4c" : "white")}'>{{data.title}}</q-item-section> 
            </q-item>
            <q-separator inset v-if='data.separator'></q-separator>
          </q-list>
      </q-scroll-area>
    </q-drawer> 
    <q-page-container >
      <KeepAlive >   
        <component :is="component" 
          v-for="account in data.account" :key="account.actif"
          :title="account"
        />
      </KeepAlive>
    </q-page-container>
    <!-- <q-page-container>
          <q-dialog v-model="account.dialog" persistent>
              <ghost-wrapper v-model:one='account.model'>
                  <template v-slot='props'>
                      <q-card style='width: 50vw;'>
                          <q-card-section v-if='props.clones.one != null'>
                              <business-contact-edit view-type="user" ref='account' v-model='props.clones.one' @update-password='updatePassword'></business-contact-edit>
                          </q-card-section>
                          <q-card-actions align="right">
                              <q-btn color='primary' :disable='props.identical' label="Enregistrer"   @click='updateContact(props.clones.one)'></q-btn>
                              <q-btn type="reset"    :disable='props.identical' label="Réinitialiser" @click='props.reset()'></q-btn>
                              <q-btn                                            label="Fermer"        v-close-popup></q-btn>
                          </q-card-actions>
                      </q-card>
                  </template>
              </ghost-wrapper>
          </q-dialog>
          <q-dialog v-model="parametres.dialog" persistent>
              <ghost-wrapper v-model:one='parametres.tree'>
                  <template v-slot='props'>
                      <q-card style='width: 50vw;'>
                          <q-card-section>
                              <div class="text-h6">Paramètres</div>
                          </q-card-section>

                          <q-separator inset></q-separator>
                          <q-card-section v-if='props.clones.one != null'>
                              <q-tree class="col-12 col-sm-6"
                              :nodes="props.clones.one.simple"
                              node-key="id"
                              tick-strategy="leaf"
                              v-model:ticked="props.clones.one.ticked"
                              v-model:expanded="props.clones.one.expanded"
                              default-expand-all
                              >
                                  <template v-slot:default-body="prop">
                                      <div>
                                          <span class="text-weight-bold text-italic">@{{prop.node.comment}}</span>
                                      </div>
                                  </template>
                              </q-tree>
                          </q-card-section>
                          <q-card-actions align="right">
                              <q-btn color='primary' :disable='props.identical' label="Enregistrer"   @click='updateParametres(props.clones.one)'></q-btn>
                              <q-btn type="reset"    :disable='props.identical' label="Réinitialiser" @click='props.reset()'></q-btn>
                              <q-btn                                            label="Fermer"        v-close-popup></q-btn>
                          </q-card-actions>
                      </q-card>
                  </template>
              </ghost-wrapper>
          </q-dialog>
          <q-dialog v-model="headBar.demandeCollecte.dialog" persistent>
              <ghost-wrapper v-model:one='parametres.tree'>
                  <template v-slot='props'>
                      <q-card style='width: 50vw;'>
                          <q-card-section>
                              <div class="text-h6">Quelle est votre demande ?</div>
                          </q-card-section>
                          <q-separator inset></q-separator>
                          <q-card-section>
                              <q-select option-label='value' option-disable='disable' emit-value map-options v-model="headBar.demandeCollecte.motif" :options="headBar.demandeCollecte.motifOptions" label="Motif"></q-select>
                          </q-card-section>
                          <q-card-section>
                              <q-input v-model="headBar.demandeCollecte.message" label="Explications détaillées" filled maxlength='1024' rows='10' type="textarea"></q-input>
                          </q-card-section>
                          <q-separator inset></q-separator>
                          <q-card-actions align="right">
                              <q-btn color='primary' :disable='headBar.demandeCollecte.message.length == 0' label="Envoyer"   @click='envoieDemande'></q-btn>
                              <q-btn                                                                        label="Fermer"    v-close-popup></q-btn>
                          </q-card-actions>
                      </q-card>
                  </template>
              </ghost-wrapper>
          </q-dialog>
          <div id='vue-body'></div>
      </q-page-container> -->
  </q-layout></div>
</template>

<script setup>
import InformationsGenerales from 'components/InformationsGenerales.vue'
import ContactsAssocies from '../components/ContactsAssocies.vue'
import ConditionsDAcces from '../components/ConditionsDAcces.vue'
import CalendrierCollectes from '../components/CalendrierCollectes.vue'
import DonneesBrutes from '../components/DonneesBrutes.vue'
import AttestationDeValorisation from '../components/AttestationDeValorisation.vue'
import RegistreDeSuiviDeDechets from '../components/RegistreDeSuiviDeDechets.vue'
import MenuPrincipal from './MenuPrincipal.vue'
import HeaderSite from './HeaderSite.vue'
import { onMounted } from 'vue'
import { ref } from 'vue'

// defineProps(['title'])
const leftDrawerOpen = ref(false)

function toggleLeftDrawer () {
  leftDrawerOpen.value = !leftDrawerOpen.value
}
 
const components = {
  InformationsGenerales,
  ContactsAssocies,
  ConditionsDAcces,
  CalendrierCollectes,
  DonneesBrutes,
  AttestationDeValorisation,
  RegistreDeSuiviDeDechets
  }

var component

// defineOptions({
//   name: 'MainLayout'
// })

// export default {
//     el: '#q-app',
//     data: function () {
//         return {
const datas = [
  {
  leftMenu: [
    {icon: 'home'          , title: 'Tableau de bord'      , path: 'clients/welcome'    , selected: true, separator: true},
    {icon: 'settings'      , title: 'Informations du site' , path: 'clients/infos'      , selected: false, separator: false, children: [
        {title: 'Informations générales'            , path: InformationsGenerales        , selected: false},
        {title: 'Contacts associés'                 , path: ContactsAssocies         , selected: false},
        {title: 'Conditions d\'accès'               , path: ConditionsDAcces            , selected: false},
    ]},
    {icon: 'fas fa-truck'    , title: 'Informations des collectes', path: 'clients/collectes'              , selected: false, separator: false, children: [
        {                     title: 'Calendrier'                , path: CalendrierCollectes , selected: false},
        {                     title: 'Données brutes'            , path: DonneesBrutes    , selected: false},
        {                     title: 'Documents de traçabilité'  , path: 'clients/collectes_tracabilite', selected: false, children: [
            {                     title: 'Attestation de valorisation' , path: AttestationDeValorisation, selected: false},
            {                     title: 'Registre de suivi de déchets', path: RegistreDeSuiviDeDechets   , selected: false},
        ]},
    ], opened: true},
  ],
  account: [{
    actif: 1,
    civilite: 0,
      client: [{
        code_trackdechet: "7364",
        contact_gestionnaire: null,
        contact_juridique: 230,
        contact_principal: 230,
        contrat: "2012-05-14",
        created_at: "2022-02-09T20:46:05.000000Z",
        email: "jonathan@mouaip.info",
        entreprise: {
          adresse_administrative: "25 Rue Solférino, 92170 Vanves, France",
          created_at: "2022-02-09T20:46:04.000000Z",
          id: 1712,
          raison_sociale: "2AD Architecture",
          siret: "39066507300032",
          suppression: 0,
          updated_at: "2022-02-09T20:46:04.000000Z"
        }}],
    created_at: null,
    email: "jonathan@mouaip.info",
    email_verified_at: null,
    id: 230,
    invitation_envoyee: 1,
    ip: "192.168.56.1",
    migration_token: "",
    nom: "Michu",
    parametres: [
      { 
        id: 31,
        notification_enlevement: 0,
        notification_passage: 0,
        notification_pesee: 0,                
        user_id: 230
      }
    ],
    pointcollectes: [
      {
        client_id: 1712,
        id: 1712,
        nom: "2AD Architecture",
        raison_sociale: "2AD Architecture",
        siret: "39066507300032"
      }
    ],
    portable: "",
    poste: "",
    prenom: "Michu",
    telephone: "0123456789",
    updated_at: "2024-04-17T11:16:32.000000Z"
  }],
  string: '',
  dialog: false,
  selected: null,
  pointcollectsFiltered: []
  //     }
  // },
  // methods: { 
  //   // loadContact: function(contact) {
  //   //   var vm = this
  //   //   axios.post('http://192.168.56.104/api/v1.0/client/compte', {}, function(data) {
  //   //       vm.account.model = data.result
  //   //   })
  //   // }
  // },
  // mounted: function() {
  //   // var vm = this
  //   // vm.loadContact()
  // }
  }
] 

const selected = function(node, noHistoryChange) {
      var vm = this
      tree = new TreeModel();
      root = tree.parse({children: vm.leftMenu});
      root.walk(function (localNode) {localNode.model.selected = localNode.model.path == node.path});
      if (vm.currentComponent != node.path.replace(/\//g, '_')) {
          if (currentComponent != null)
              currentComponent.unmount()

          vm.currentComponent = node.path.replace(/\//g, '_')
          pointcollecte.changedNotification = null
          vm.showPointcollecteSelector(false)
          loadComponent(document.getElementById('vue-body'), '/template/clients/'+vm.currentComponent)
          if (noHistoryChange !== true)
              history.pushState({path: node.path}, "")
      }
  }

</script>

<style>
  html, body {
    background-color: #f2efef;
  }
  #userForm i {
    color: #003f6e;
  }
  #q-app, body, html {
    width: 100%;
    direction: ltr;
  }
  .error {
    color: red;
    font-size: smaller;
    font-style: italic;
    font-weight: 500;
    margin-top: 4px;
  }
  .active-menu-link {
    color: #003f6e;
    font-weight: bolder;
  }
  header {
    background-color: white !important;
  }
  #leftpanel {
    background-color: #003f6e;
  }
  #leftpanel *{
    color: white;
  }
  #leftpanel hr.q-separator{
    background-color: white;
    margin-top: 0.5rem;
    margin-bottom: 0.5rem;
    height: 0.05rem;
    width: 80%;
    margin-left: auto;
    margin-right: auto;
  }
  #q-app header * {
    color: #003f6e;
  }
  aside {
    width: 300px !important;
  }
  .logowrapper {
    min-width: 300px;
    width: 300px;
    text-align: center;
    border-right-width: 1px;
    border-right-color: black;
    border-right-style: inset;
  }
  .notificationicon {
    color: #003f6e;
  }
  #passwdComplexity {
    margin-bottom: 0.5rem;
    height: 0.1rem;
    position: absolute;
    top: 0;
    width: 100%;
  }
</style>
<style>
/*pour la bulle d'aide*/
  .araccourcir {
      overflow: auto;
      white-space: initial;
  }
  #chatwrapper {
      display: none;
      position: fixed;
      bottom: 0.1em;
      right: 0.1em;
      overflow: hidden;
      z-index: 99999999;
  }
  #chatframe {
      animation: frameClosed .25s;
      width: 6em;
      height: 6em;
      border-radius: 1em 1em 1em 1em;
      border: none;
      overflow: hidden;
      background-color: #FFFD;
  }
  #chatframe.opened {
      animation: frameOpened .25s;
      width: 20rem;
      height: 20rem;
      height: 23rem;
      border-top-left-radius: 0rem;
      border-top-right-radius: 0rem;
      border-bottom-left-radius: 1rem;
      border-bottom-right-radius: 1rem;
  }
  #chatwrapper.opened {
      width: 20rem;
      height: 20rem;
      height: 23rem;
  }
  #chatframe.reduced {
      width: 1.5em;
      height: 1.5em;
      border-radius: 1rem;
  }
  #chatwrapper.reduced {
      width: 1.5em;
      height: 1.5em;
      border-radius: 1rem;
  }
  @keyframes frameOpened {
      from {
      transform: scale(0);
      }
      to {
      transform: scale(1);
      }
  }
  @keyframes frameClosed {
      from {
      transform: scale(1);
      }
      to {
      transform: scale(0);
      }
  }
</style>