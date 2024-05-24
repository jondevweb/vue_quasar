<script setup>
import { ref } from 'vue'

import { inject } from 'vue'

const data = inject('data')

const props = [
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
          With so much content to display at once, and often so little screen real-estate,
          Cards have fast become the design pattern of choice for many companies, including
          the likes of Google and Twitter.
        </q-tab-panel>
      </q-tab-panels>
    </q-card> 
  </div>
</template>  
    
  