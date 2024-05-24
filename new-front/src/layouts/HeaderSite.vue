<script setup>
import { ref } from 'vue'

defineProps(['title'])
const leftDrawerOpen = ref(false)

function toggleLeftDrawer () {
  leftDrawerOpen.value = !leftDrawerOpen.value
}
</script>

<template> 
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
                <div class="row items-center no-wrap text-center" style='font-weight: normal;font-size: smaller;line-height: normal; color: #003F6E' v-bind="title">
                    {{ title.account[0].prenom }}
                    <br>
                    {{ title.account[0].nom }}
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
</template>