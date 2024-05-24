<script setup>
import { ref, onMounted } from 'vue'
import { copyToClipboard } from 'quasar'
import InputCard from '../components/sous_components/InputCard.vue'
import TooltipInput from '../components/sous_components/TooltipInput.vue'
import EventCalendar from '../components/sous_components/EventCalendar.vue'
import { useRoute } from 'vue-router';

import { inject } from 'vue'
import axios from 'axios'

const data = inject('data')

const propes = [
          {
            code_trackdechet: "7364",
            contact_gestionnaire: null,
            contact_juridique: 230,
            contact_principal: 230,
            contrat: "2012-05-14",
            created_at: "2022-02-09T20:46:05.000000Z",
            email: "jonathan@mouaip.info",
            entreprise: [
              {
                adresse_administrative: "25 Rue Solférino, 92170 Vanves, France",
                created_at: "2022-02-09T20:46:04.000000Z",
                id: 1712,
                raison_sociale: "2AD Architecture",
                siret: "39066507300032",
                suppression: 0,
                updated_at: "2022-02-09T20:46:04.000000Z"
              }
            ],
            prenom: "Michu",
            nom: "Michu",
            telephone: "0123456789",
          }
        ]

const tab = ref('info')

function formatDate(date){
  date = (new Date(date.substr(0, 10).toString().split('-').join(', '))).toLocaleString("en-US");
  if(isNaN(date.slice(1, 2))){
    date = 0 + date
  }
  if(isNaN(date.slice(4, 5))){
    date = date.slice(0, 3).concat(0 + date.slice(3, 10))
  }
  return date.substr(0, 10);
}

defineOptions({
  name: 'InformationsGenerales'
})

// copyToClipboard('some text')
//   .then(() => {
//     // success!
//   })
//   .catch(() => {
//     // fail
//   })

const datar = ref([null]);
const error = ref(null);

async function fetchData() {
  error.value = null;
  datar.value = null;

  try {
    const response = await fetch('https://min-api.cryptocompare.com/data/pricemulti?fsyms=BTC,ETH&tsyms=USD,EUR');
    console.log('hello')
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    // const contentType = response.headers.get('content-type');
    // if (contentType && contentType.includes('application/json')) { console.log('hello')
      const result = await response.json();
      datar.value = result;
      console.log(datar)
    // } else {
    //   throw new Error('Received non-JSON response');
    // }
  } catch (err) {
    error.value = err.message;
    console.error('Error fetching data:', err);
  }
}
</script>

<template>
  <div>
    <button @click="fetchData">Fetch Data</button>
    <div v-if="datar">
      <p>Data: {{ datar}}</p>
    </div> 
    <div v-else>
      <p>pas de data {{ datar}}</p>
    </div>  
  </div>
<hr/>
{{ data }}
<hr/>
{{ data.id }}
  <div class="q-pa-lg">
    <q-card class="my-card" flat bordered v-for="client in propes" 
      :key="client.code_trackdechet">
      <q-card-section >
        <div class="text-h6">Établissement principal</div>
        <div class="text-subtitle2" v-for="entreprise in client.entreprise" 
          :key="entreprise.siret">{{ entreprise.raison_sociale}}</div>
      </q-card-section>
      <q-tabs v-model="tab" class="text-teal">
        <q-tab label="Information" name="info" />
        <q-tab label="Contact" name="contact" />
      </q-tabs>
      <q-separator />
      <q-tab-panels v-model="tab" animated>
        <q-tab-panel name="info" v-for="entreprise in client.entreprise" 
          :key="entreprise.siret"> 
          <q-field label="Raison sociale" stack-label>
            <InputCard>{{ entreprise.raison_sociale}}</InputCard>
            <TooltipInput>{{ entreprise.raison_sociale}}</TooltipInput>
          </q-field>
          <q-field label="SIRET" stack-label>
            <InputCard>{{ entreprise.siret}}</InputCard>
            <TooltipInput>{{ entreprise.siret}}</TooltipInput>
          </q-field>
          <q-field label="Adresse" stack-label>
            <InputCard>{{ entreprise.adresse_administrative}}</InputCard>
            <TooltipInput>{{ entreprise.adresse_administrative}}</TooltipInput>  
          </q-field>
          <q-field label="Date contrat" stack-label>
            <InputCard >{{ formatDate(entreprise.created_at) }}</inputCard>
            <TooltipInput>{{ formatDate(entreprise.created_at) }}</TooltipInput>
            <EventCalendar></EventCalendar>
          </q-field>
          <q-field label="Mail de contact" stack-label>
            <div>
              <q-icon name="mail" color="grey" style="padding-left: 10px; font-size: 25px; top: 3px;" />
            </div>
            <InputCard>{{ client.email}}</InputCard>
            <TooltipInput>{{ client.email}}</TooltipInput> 
          </q-field>
          <q-field label="Téléphone" stack-label>
            <div >
              <q-icon name="phone" color="grey" style="padding-left: 10px; font-size: 25px; top: 3px;" />
            </div>
            <InputCard>{{ client.telephone }}</InputCard>
            <TooltipInput>{{ client.telephone }}</TooltipInput>  
          </q-field>
        </q-tab-panel>
        <q-tab-panel name="contact">
         <q-item>
            <q-item-section avatar>
               <q-avatar>
                  <img
                     src="~assets/user.svg"
                  >
               </q-avatar>
            </q-item-section>
            <q-item-section>
               <q-item-label>Contact juridique</q-item-label>
               <q-item-label caption>{{ client.prenom}} {{ client.nom}}</q-item-label>
            </q-item-section>
         </q-item>
         <q-separator />
         <div name="info" v-for="entreprise in client.entreprise" 
            :key="entreprise.siret"> 
            <q-field label="Mail de contact" stack-label>
               <div>
               <q-icon name="mail" color="grey" style="padding-left: 10px; font-size: 25px; top: 3px;" />
               </div>
               <InputCard>{{ client.email}}</InputCard>
               <TooltipInput>{{ client.email}}</TooltipInput> 
            </q-field>
            <q-field label="Téléphone" stack-label>
               <div >
               <q-icon name="phone" color="grey" style="padding-left: 10px; font-size: 25px; top: 3px;" />
               </div>
               <InputCard>{{ client.telephone }}</InputCard>
               <TooltipInput>{{ client.telephone }}</TooltipInput>  
            </q-field>
            <q-field label="Portable" stack-label>
               <div >
               <q-icon name="phone" color="grey" style="padding-left: 10px; font-size: 25px; top: 3px;" />
               </div>
               <InputCard></InputCard>
               <TooltipInput></TooltipInput>  
            </q-field>
          </div>
        </q-tab-panel>
      </q-tab-panels>
    </q-card> 
  </div>

  <!-- <div id='TOTORO' class="q-pa-md fit column wrap justify-start items-start content-start">
    <LabelMenu >Informations générales</LabelMenu>
    <div class='q-gutter-md fit row wrap justify-start items-stretch content-start'>
      <q-card style='min-width: 35em;'>
        <TitleCard >Établissement principal</TitleCard>
        <EtablissementPrincipal :title="title" /> -->

              <!-- <q-tab-panels v-model="modelValue" animated style='width: 100%;'>
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
                  </q-card-section>ddd
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
    </q-tab-panels> -->
          
   <!--   </q-card>
      <q-card style='min-width: 30em;'>
        <TitleCard >Contact juridique</TitleCard> 
        <q-card-section>
          <q-card bordered>
            <div class="text-center" style='line-height: 0;font-size: 6rem; margin-top: 0.5rem;' >
              <img src="../assets/user.svg" />
            </div>
            <q-card-section style='line-height: 0;'>
              <div class="text-subtitle1 text-center text-capitalize" :style='{color: primaryColor}'>{{title.prenom}} {{title.nom}}</div>
              <div class="text-subtitle1 text-center text-weight-thin text-italic">&nbsp;</div>
            </q-card-section>
            <ContactCommon :title="title" />
          </q-card> -->
            <!-- <business-contact v-model='contact_juridique.model' view-type='clientlight'></business-contact> -->
       <!-- </q-card-section>
      </q-card>
    </div>-->
    <!-- <div class='q-gutter-md fit row wrap justify-start items-stretch content-start' style='margin-top: 1rem' v-if='gestionnaire.model.id'> -->
   <!--  <div class='q-gutter-md fit row wrap justify-start items-stretch content-start' style='margin-top: 1rem'>
      <q-card>
        <TitleCard >Gestionnaire</TitleCard>
        <q-card-section horizontal class='toto'>
          <q-card-section style='min-width: 35em;'> -->
              <!-- <business-societe readonly v-model='gestionnaire.model' type='client'></business-societe> -->
       <!--    </q-card-section> -->
         
          <!-- <business-contact v-model='contact_gestionnaire.model' view-type='clientlight'></business-contact> -->
    <!--     </q-card-section>
      </q-card>
    </div>
  </div> -->

</template>

<style>
  .sticky-header-table {
      height: 50vh;
  }

  .my-card{
    width: 60%;
    margin: auto;
  }

  InputCard:hover > q-icon{
    visibility: visible;
  }
  
</style>