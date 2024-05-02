<template>
  <div id='q-app'>
  <q-layout view="lHh Lpr lFf">
    <q-header elevated>
      <q-toolbar>
        <q-btn
          flat
          dense
          round
          icon="menu"
          aria-label="Menu"
          @click="toggleLeftDrawer"
        />



        <!-- <q-toolbar-title>
          Quasar App
        </q-toolbar-title> -->
        <q-space></q-space>
        <!-- <q-select

            v-model="account.selected"

            :options="account.pointcollectsFiltered"

            v-if='visible'
        > -->
        <q-select
            filled
            
            use-input
            input-debounce="0"
            label="Point de collecte"

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
        <!-- <div>Quasar v{{ $q.version }}</div> -->
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
            <div v-for="data in datas"
            :key="data.title"
            v-bind="data">{{ data.account.model[0].actif }}</div>
              <!-- <template v-slot:label>
                  <div class="row items-center no-wrap">yyyy
                  <div class="text-center" style='font-weight: normal;font-size: smaller;line-height: normal;' >yhgvdfs
                     <br>
                  </div>
                  </div>
              </template> -->
              <q-list>
                  <q-item clickable v-close-popup @click="account.dialog = true">
                  <q-item-section>
                      <q-item-label>Mes informations</q-item-label>
                  </q-item-section>
                  </q-item>

                  <!--q-item clickable v-close-popup @click="parametres.dialog = true">
                  <q-item-section>
                      <q-item-label>Mes paramètres</q-item-label>
                  </q-item-section>
                  </q-item-->

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

    <q-drawer
      v-model="leftDrawerOpen"
      show-if-above
      bordered
    >
      <q-list>
        <q-item-label
          header
        >
          Essential Links
        </q-item-label>

        <EssentialLink
          v-for="link in linksList"
          :key="link.title"
          v-bind="link"
        />
      </q-list>
    </q-drawer>

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout></div>
</template>

<script setup>
import { ref } from 'vue'
import EssentialLink from 'components/EssentialLink.vue'

// defineOptions({
//   name: 'MainLayout'
// })

// export default {
//     el: '#q-app',
//     data: function () {
//         return {
const datas = [
  {
    allowedIps: ['192.168.1.17', '109.190.64.160'],
    account: {
      model: [
        {
          actif: 1,
          civilite: 0,
            clients: [ 1712 ],
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
        }
      ]
      ,
      string: '',
      dialog: false,
      selected: null,
      pointcollectsFiltered: []
    }
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
console.log(datas[0].account.model[0].nom);
const linksList = [
  {
    title: 'Docs',
    caption: 'quasar.dev',
    icon: 'school',
    link: 'https://quasar.dev'
  },
  {
    title: 'Github',
    caption: 'github.com/quasarframework',
    icon: 'code',
    link: 'https://github.com/quasarframework'
  },
  {
    title: 'Discord Chat Channel',
    caption: 'chat.quasar.dev',
    icon: 'chat',
    link: 'https://chat.quasar.dev'
  },
  {
    title: 'Forum',
    caption: 'forum.quasar.dev',
    icon: 'record_voice_over',
    link: 'https://forum.quasar.dev'
  },
  {
    title: 'Twitter',
    caption: '@quasarframework',
    icon: 'rss_feed',
    link: 'https://twitter.quasar.dev'
  },
  {
    title: 'Facebook',
    caption: '@QuasarFramework',
    icon: 'public',
    link: 'https://facebook.quasar.dev'
  },
  {
    title: 'Quasar Awesome',
    caption: 'Community Quasar projects',
    icon: 'favorite',
    link: 'https://awesome.quasar.dev'
  }
]

const leftDrawerOpen = ref(false)

function toggleLeftDrawer () {
  leftDrawerOpen.value = !leftDrawerOpen.value
}
</script>
