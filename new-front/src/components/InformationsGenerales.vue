<script setup>
import ContactCommon from './sousComponents/ContactCommon.vue'
import EtablissementPrincipal from './sousComponents/EtablissementPrincipal.vue'
import TitleCard from './sousComponents/TitleCard.vue'
import LabelMenu from './sousComponents/LabelMenu.vue'
defineProps(['title'])

function copyToClipboard(text){
        Quasar.copyToClipboard(text)
        .then(() => {notify('positive', 'Données placées dans le presse-papier.')})
        .catch(() => {notify('negative', 'Problème d\'accès au presse-papier.')})
        }
</script>

<template>
  <div id='TOTORO' class="q-pa-md fit column wrap justify-start items-start content-start">
    <LabelMenu >Informations générales</LabelMenu>
    <div class='q-gutter-md fit row wrap justify-start items-stretch content-start'>
      <q-card style='min-width: 35em;'>
        <TitleCard >Établissement principal</TitleCard>
        <EtablissementPrincipal :title="title" />

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
          
      </q-card>
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
          </q-card>
            <!-- <business-contact v-model='contact_juridique.model' view-type='clientlight'></business-contact> -->
        </q-card-section>
      </q-card>
    </div>
    <!-- <div class='q-gutter-md fit row wrap justify-start items-stretch content-start' style='margin-top: 1rem' v-if='gestionnaire.model.id'> -->
    <div class='q-gutter-md fit row wrap justify-start items-stretch content-start' style='margin-top: 1rem'>
      <q-card>
        <TitleCard >Gestionnaire</TitleCard>
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