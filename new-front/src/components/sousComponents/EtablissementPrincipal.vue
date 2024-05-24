<script setup>
defineProps(['title'])

</script>
<template>
    <q-card-section v-for="client in title.client" :key="client.code_trackdechet">  
        <q-form :ref='ref' @submit="submit" @reset='reset' style='width: 100%;' v-if='client!= null'>
            <!-- <ghost-wrapper :ref='ref+"ghostwrapper"' v-model:one='modelValue'>
              <template v-slot='props'> -->
            <q-card flat style='width: 100%;' >
                <q-card-section>
                    <q-input readonly
                        filled label="Raison sociale"    v-model="client.entreprise.raison_sociale"         
                    :simplify='true' :required='true' />
                    <q-icon name="far fa-clipboard" @click='copyToClipboard("hello")'></q-icon>
                    <q-input readonly
                        filled label="SIRET"    v-model="client.entreprise.siret"         
                        :simplify='true' :required='true' />
                    <q-icon name="far fa-clipboard" @click='copyToClipboard("hello")'></q-icon>
                    <q-input readonly
                        filled label="Adresse"    v-model="client.entreprise.adresse_administrative"         
                        :simplify='true' :required='true' />
                    <q-icon name="far fa-clipboard" @click='copyToClipboard("hello")'></q-icon>
                    <q-input readonly
                        filled label="Date contrat"    v-model="client.contrat"         
                        :simplify='true' :required='true' />
                    <q-icon name="far fa-clipboard" @click='copyToClipboard("hello")'></q-icon>
                    <!--  
                    <input-date  label="Date contrat"      v-model="props.clones.one.contrat"                           :readonly='readonly'                  :required='true' v-if='modelValue.contrat != undefined'></input-date> -->
                    <q-input readonly
                        filled label="Mail de contact" v-model="client.email"         
                        :simplify='true' :required='true' />
                    <q-icon name="far fa-clipboard" @click='copyToClipboard("hello")'></q-icon>
                    <!-- <input-email label='Mail de contact'   v-model="props.clones.one.email"                             :readonly='readonly'                  :required='true'
                                v-if='type == "client" || type == "exutoire"'
                    ></input-email> -->
                    <q-input readonly
                        filled label="Téléphone" v-model="telephone"         
                        :simplify='true' :required='true' />
                    <q-icon name="far fa-clipboard" @click='copyToClipboard("hello")'></q-icon>
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
                </q-card-section>
            </q-card>
        </q-form>
    </q-card-section>
</template>