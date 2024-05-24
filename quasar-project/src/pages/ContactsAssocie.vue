<script setup>
import { ref } from 'vue'
import InputCard from '../components/sous_components/InputCard.vue'
import TooltipInput from '../components/sous_components/TooltipInput.vue'
import { inject } from 'vue'

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
            telephone: "0123456789",
          }
        ]
const tab = ref('info')

defineOptions({
  name: 'InformationsGenerales'
})
</script>

<template>
{{ data.id }}
<hr/>
{{ data.label}}
  <div class="q-pa-lg" v-if="data.label == '2AD Architecture'">
      <q-card class="my-card" flat bordered v-for="client in propes" 
      :key="client.code_trackdechet">
         <q-item>
            <q-item-section avatar>
               <q-avatar>
                  <img
                     src="~assets/user.svg"
                  >
               </q-avatar>
            </q-item-section>
            <q-item-section>
               <q-item-label>Contact principal et juridique</q-item-label>
               <q-item-label caption v-for="entreprise in client.entreprise" 
               :key="entreprise.siret">{{ entreprise.raison_sociale}}</q-item-label>
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
      </q-card> 
  </div>
  <div class="q-pa-lg" v-else>
      <p>Pas de contact associés</p>
  </div>
</template>  
