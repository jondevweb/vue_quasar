<template v-slot:append>
    <div class="self-center full-width no-outline" tabindex="0" style="padding-left: 10px">
        <slot />
    </div>
    <q-icon name="fas fa-copy" class="hidden" />
</template>  

<style>
.hidden{
    visibility: hidden;
}

</style>