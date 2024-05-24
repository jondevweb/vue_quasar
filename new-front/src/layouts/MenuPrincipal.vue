<script setup>
import { ref } from 'vue'

defineProps(['title'])
const leftDrawerOpen = ref(false)

function toggleLeftDrawer () {
  leftDrawerOpen.value = !leftDrawerOpen.value
}

</script>

<template> 
    <q-drawer v-model="leftDrawerOpen"
        id='leftpanel'>
        <q-scroll-area class="fit">
            <q-list class="rounded-borders" v-for="data in title.leftMenu" v-bind:key="data.title">
            <q-expansion-item v-if='data.children != undefined && (!data.hidded || showAll)'
                            v-bind:icon='data.icon'
                            v-bind:label='data.title'
                            v-bind:header-inset-level='0'
                            v-bind:content-inset-level='1'
                            :class="{'short-inset': level == 1}"
                            v-model='data.opened'            
                        >
                <q-item style='width: 107%;' clickable v-ripple v-for="child in data.children" :key="child.title" @click="toggleLeftDrawer" >
                <q-item-section @click="component = child.path ">
                    {{child.title}}
                    <q-expansion-item v-if='child.children != undefined && (!data.hidded || showAll)'>
                    <q-item clickable v-for="children in child.children" :key="children.title">
                        <q-item-section>{{children.title}}</q-item-section>
                    </q-item> 
                    </q-expansion-item> 
                </q-item-section> 
                </q-item>     
            </q-expansion-item>                       
            <q-item clickable v-ripple v-if='data.children == undefined && (!data.hidded || showAll)'
                active-class='active-menu-link'
                v-bind:active='data.selected'
                @click="$emit('selected', data)"
            >
                <q-item-section v-if='data.icon != undefined  && (!data.hidded || showAll)' avatar>
                    <q-icon v-bind:name='data.icon' :style='data.style' :color='data.selected ? "#b1dc4c" : "white"'></q-icon>
                </q-item-section>
                <q-item-section :style='{color: (data.selected ? "#b1dc4c" : "white")}'>{{data.title}}</q-item-section> 
            </q-item>
            <q-separator inset v-if='data.separator'></q-separator>
            </q-list>
        </q-scroll-area>
    </q-drawer>
</template>  