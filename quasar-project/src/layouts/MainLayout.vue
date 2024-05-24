<template>
  <q-layout view="hHh Lpr lff" class="shadow-2 rounded-borders">
    <q-header elevated >
      <q-toolbar>
        <q-btn flat @click="drawer = !drawer" round dense icon="menu" />
        <q-toolbar-title>Header</q-toolbar-title>
        <div class="q-gutter-md row" >
          <q-select
            filled
            v-model="model"
            clearable
            use-input
            hide-selected
            fill-input
            input-debounce="0"
            :options="options"
            label="Point de collecte"
            style="width: 250px;"
          >
            <template v-slot:no-option>
              <q-item>
                <q-item-section class="text-grey">
                  No results
                </q-item-section>
              </q-item>
            </template>
          </q-select>
        </div>
      </q-toolbar>    
    </q-header>
    <q-drawer
      v-model="drawer"
      show-if-above
      :mini="miniState"
      @mouseover="miniState = false"
 
      :width="300"
      :breakpoint="500"
      bordered
      :class="$q.dark.isActive ? 'bg-grey-9' : 'bg-grey-3'"
    >
      <!-- @mouseout="miniState = true" -->
      <q-scroll-area class="fit" :horizontal-thumb-style="{ opacity: 0 }">
        <q-list padding v-for="menu in leftMenu"
          :key="menu.title" style="padding: 0px;">
          <q-item v-if="menu.children"
            clickable
            v-ripple         
            style="padding: 0px"
          >
            <q-expansion-item
              clickable
              :icon="menu.icon"
              :label="menu.title"
              style="width: 100%"
            >
              <q-item clickable v-for="childMenu in menu.children"
                :key="childMenu.title" style="margin-left: 56px;" >
                <div v-if="!childMenu.children">
                  <!-- :active="selected === childMenu.title" -->
                  <q-item-section v-if="model != null">
                    <q-tabs 
                      v-model="tab"
                      class="text-dark"
                      active-color="primary"
                      indicator-color="primary"
                      narrow-indicator>
                      <q-route-tab
                        :label=childMenu.title
                        :to="{
                          name: childMenu.name
                        }"

                        exact
                        replace
                        style="padding: 0px; 
                        justify-content: flex-start;
                        min-height: 0px;
                        text-transform: none;"
                      />
                      <!-- @click="goToChild = true"  -->
                    </q-tabs>
                    <!-- <router-link :to="{
                        name: childMenu.name
                      }" @click="goToChild = true" replace>
                      <q-item-label>
                        {{ childMenu.title }}
                      </q-item-label>
                    </router-link> -->
                  </q-item-section>
                  <q-item-section v-else>
                    <div v-if="model = null">
                      <router-link :to="{
                          name: 'home'
                        }" replace >
                        <q-item-label>
                          {{ childMenu.title }}
                        </q-item-label>
                      </router-link>  
                    </div> 
                    <div v-else>
                      <q-item-label>
                        {{ childMenu.title }}
                      </q-item-label>
                    </div>
                  </q-item-section>
                </div>
                <div v-else style="position: relative; right: 16px;">
                  <q-expansion-item
                    clickable
                    :icon="childMenu.icon"
                    :label="childMenu.title"
                    style="width: 100%;"
                  >
                    <q-item clickable v-for="grandChild in childMenu.children"
                      :key="grandChild.title" style="margin-left: 15px;" >
                      <q-item-section v-if="model != null">
                        <q-tabs 
                          v-model="tab"
                          class="text-dark"
                          active-color="primary"
                          indicator-color="primary"
                          narrow-indicator>
                          <q-route-tab
                            :label=grandChild.title
                            :to="{
                              name: grandChild.name
                            }"

                            exact
                            replace
                            style="padding: 0px; 
                            justify-content: flex-start;
                            min-height: 0px;
                            text-transform: none;"
                          />
                        </q-tabs>
                      </q-item-section>
                      <q-item-section v-else>
                        <div v-if="model = null">
                          <router-link :to="{
                              name: 'home'
                            }" replace >
                            <q-item-label>
                              {{ grandChild.title }}
                            </q-item-label>
                          </router-link>  
                        </div> 
                        <div v-else>
                          <q-item-label>
                            {{ grandChild.title }}
                          </q-item-label>
                        </div>
                      </q-item-section>
                    </q-item>
                  </q-expansion-item>
                </div>

              </q-item>
            </q-expansion-item>
          </q-item>
          <q-item v-else
            clickable
            v-ripple
            :active="selected === menu.title" 
            @click="selected = menu.title"
          >
            <q-item-section
              v-if="menu.icon"
              avatar
            >
              <q-icon :name="menu.icon" />
            </q-item-section>
            <q-item-section>
              <q-tabs 
                v-model="tab"
                class="text-dark"
                narrow-indicator>
                <q-route-tab
                  :label=menu.title
                  :to="{
                    name: 'home'
                  }"

                  exact
                  replace
                  style="padding: 0px; 
                  justify-content: flex-start;
                  min-height: 0px;
                  text-transform: none;"
                />
                <!-- @click="goToChild = true"  -->
              </q-tabs>
              <!-- <router-link :to="{
                    name: 'home'
                  }" replace >
                <q-item-label active-class="my-menu-selected">
                  {{ menu.title }}
                </q-item-label>
              </router-link> -->
            </q-item-section>
          </q-item>
          <q-separator v-if="!menu.children" style="width: 90%; margin: 8px 16px"/>
        </q-list>
      </q-scroll-area>
    </q-drawer>
    <q-page-container v-if="model != null">
        <router-view />
    </q-page-container>
    <q-page-container v-else>
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { ref, provide } from 'vue'

const drawer = ref(false)
const miniState = ref(true)

const pointcollectes = [
  { 
    label: 'One', 
    raison_sociale: 'One', 
    id: 1 
  },
  { 
    label: 'Two', 
    raison_sociale: 'Two', 
    id: 2 
  },
  {
    label: "2AD Architecture",
    raison_sociale: "2AD Architecture",
    id: 1712
  }
]

const model = ref(null)
const multipleOptions = []
pointcollectes.forEach((element) => multipleOptions.push(element))
const options = ref(multipleOptions)

provide('data', model)

// const goToChild = ref(false)

const tab = ref('')

defineOptions({
  name: 'MainLayout'
})

const selected = ref('Tableau de bord')

// function selectedComponent(name) {
//   drawer.value = false
//   selected.value = name
//   setTimeout(() => {  drawer.value = true; }, 800);
  
// }

const leftMenu = [
  {
    title: 'Tableau de bord',
    icon: 'home',
  },
  {
    title: 'Informations du site',
    icon: 'settings',
    path: '/client',
    children: [
      {
        title: 'Informations générales',
        name: 'informationsGenerales',

      },
      {
        title: 'Contacts associés',
        name: 'associes',

      },
      {
        title: 'Conditions d\'accès',
        name: 'acces',

      },
    ]
  },
  {
    title: 'Informations des collectes',
    icon: 'fas fa-truck',
    path: '/clients/collectes',
    children: [
      {
        title: 'Calendrier',
        name: 'CalendrierCollectes',

      },
      {
        title: 'Données brutes',
        name: 'DonneesBrutes',

      },
      {
    title: 'Documents de traçabilité',
    path: '/clients/collectes_tracabilite',
    children: [
      {
        title: 'Attestation de valorisation',
        name: 'AttestationDeValorisation',

      },
      {
        title: 'Registre de suivi de déchets',
        name: 'RegistreDeSuiviDeDechets',

      },
    ]
  },
    ]
  },
]
</script>