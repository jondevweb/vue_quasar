<script setup>
defineProps(['title'])

</script>
<template>
    <q-card-section v-for="client in title.client" :key="client.code_trackdechet">
        <q-form :ref='ref' @submit="submit" @reset='reset' style='width: 100%;' v-if='client!= null'>
            <q-input readonly
                filled label="Courriel" v-model="client.email"         
                :simplify='true' :required='true' />
            <q-icon name="far fa-clipboard"></q-icon>
            <q-input readonly
                filled label="Téléphone" v-model="telephone"         
                :simplify='true' :required='true' />
            <q-icon name="far fa-clipboard"></q-icon>
            <q-input readonly
                filled label="Portable" v-model="client.portable"         
                :simplify='true' :required='true' />
            <q-icon name="far fa-clipboard"></q-icon>
        </q-form>
    </q-card-section>
</template>