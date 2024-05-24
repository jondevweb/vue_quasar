<script setup>
defineProps(['title'])
</script>

<template>
  <div id='TOTORO' class="q-pa-md fit column wrap justify-start items-start content-start">
    <div class='text-h5 text-primary fit row no-wrap justify-start items-start content-center' style='margin-bottom: 1rem;'>
      <div style='white-space: nowrap;'>Informations générales</div>&nbsp;&nbsp;&nbsp;
    </div>
    <div class='q-gutter-md fit row wrap justify-start items-stretch content-start'>
      <q-card style='min-width: 35em;'>
        <q-card-section >
          <div class="text-h6">Établissement principal</div>
        </q-card-section>
        <q-card-section v-for="client in title.client" :key="client.code_trackdechet">    
          {{ client.entreprise }}          
          <q-form :ref='ref' @submit="submit" @reset='reset' style='width: 100%;' v-if='client!= null'>
            <!-- <ghost-wrapper :ref='ref+"ghostwrapper"' v-model:one='modelValue'>
              <template v-slot='props'> -->
          <q-card flat style='width: 100%;' >
            <q-card-section>
              <q-input readonly
                filled label="Raison sociale"    v-model="client.entreprise.raison_sociale"         
                :simplify='true' :required='true' />
              <q-input readonly
                filled label="SIRET"    v-model="client.entreprise.siret"         
                :simplify='true' :required='true' />
              <q-input readonly
                filled label="Adresse"    v-model="client.entreprise.adresse_administrative"         
                :simplify='true' :required='true' />
              <q-input readonly
                filled label="Date contrat"    v-model="client.contrat"         
                :simplify='true' :required='true' />
              <!--  
              <input-date  label="Date contrat"      v-model="props.clones.one.contrat"                           :readonly='readonly'                  :required='true' v-if='modelValue.contrat != undefined'></input-date> -->
              <q-input readonly
                filled label="Mail de contact" v-model="client.email"         
                :simplify='true' :required='true' />
              <!-- <input-email label='Mail de contact'   v-model="props.clones.one.email"                             :readonly='readonly'                  :required='true'
                          v-if='type == "client" || type == "exutoire"'
              ></input-email> -->
              <q-input readonly
                filled label="Téléphone" v-model="client.telephone"         
                :simplify='true' :required='true' />
              <!-- <input-phone label="Téléphone"         v-model="props.clones.one.telephone"                         :readonly='readonly'                  :required='true'
                          v-if='type == "client" || type == "exutoire"'
              ></input-phone>
              <input-line  label="Prénom et nom du contact figurant sur le BSD"    v-model="props.clones.one.contact"         :readonly='readonly' :simplify='true' :required='true'
                          v-if='type == "exutoire"' icon='fas fa-user'
              ></input-line> -->
            <!-- </q-card-section>
            <q-card-actions v-if='!readonly' class="q-px-md">
                <q-btn :disable='props.identical' label="Enregistrer"   @click='submit(props.clones.one)'></q-btn>
                <q-btn :disable='props.identical' label="Réinitialiser" @click='props.reset()'></q-btn>
            </q-card-actions>
          </q-card>
        </template>
      </ghost-wrapper>-->
    </q-card-section></q-card>
    </q-form> 

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





               
            </q-card-section>
        </q-card>
        <q-card style='min-width: 30em;'>
            <q-card-section>
                <div class="text-h6">Contact juridique</div>
            </q-card-section>
            <q-card-section>
                <!-- <business-contact v-model='contact_juridique.model' view-type='clientlight'></business-contact> -->
            </q-card-section>
        </q-card>
    </div>
    <!-- <div class='q-gutter-md fit row wrap justify-start items-stretch content-start' style='margin-top: 1rem' v-if='gestionnaire.model.id'> -->
    <div class='q-gutter-md fit row wrap justify-start items-stretch content-start' style='margin-top: 1rem'>
        <q-card>
            <q-card-section>
                <div class="text-h6">Gestionnaire</div>
            </q-card-section>
            <q-card-section horizontal class='toto'>
                <q-card-section style='min-width: 35em;'>
                    <!-- <business-societe readonly v-model='gestionnaire.model' type='client'></business-societe> -->
                </q-card-section>
                <q-separator vertical></q-separator>
                <!-- <business-contact v-model='contact_gestionnaire.model' view-type='clientlight'></business-contact> -->
            </q-card-section>
        </q-card>
    </div>
  </div>

</template>

<style>
    .sticky-header-table {
        height: 50vh;
    }
</style>