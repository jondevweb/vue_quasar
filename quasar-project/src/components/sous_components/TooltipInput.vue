<template v-slot:label>
    <q-tooltip class="bg-grey-8" anchor="top left" self="bottom left" :offset="[0, 8]">
        <slot />
    </q-tooltip>
</template>  