<template>
  <q-item v-if="props.children"
    clickable
    v-ripple         
    style="padding: 0px"
  >
    <q-expansion-item
        clickable
        :icon="props.icon"
        :label="props.title"
        style="width: 100%"
      >
        <q-item clickable v-for="childMenu in props.children"
          :key="childMenu.title" style="margin-left: 56px" :active="selected === childMenu.title" @click="selected = childMenu.title">
          <q-item-section @click="component = childMenu.component ">
            <q-item-label active-class="my-menu-link">{{ childMenu.title }}</q-item-label>
          </q-item-section>
        </q-item>
    </q-expansion-item>
  </q-item>
  <q-item v-else
    clickable
    v-ripple
    :active="selected === props.title" 
    @click="selected = props.title"
  >
    <q-item-section
      v-if="props.icon"
      avatar
    >
      <q-icon :name="props.icon" />
    </q-item-section>
    <q-item-section>
      <q-item-label active-class="my-menu-selected">{{ props.title }}</q-item-label>
    </q-item-section>
  </q-item>
  <q-separator v-if="!props.children" style="width: 90%; margin: 8px 16px"/>
</template>
  
<script setup>
import { ref } from 'vue'

defineOptions({
  name: 'MenuLink'
})

var component

const selected = ref('Tableau de bord')

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  icon: {
    type: String,
    default: ''
  },
  path: {
    type: String,
    default: ''
  },
  separator: Boolean,
  children: {
    type: Object,
    default: () => {}
  }
  // component
})

// const post = await fetch(`/api/v1.0/client/client/1712/contact/list`).then((r) => r.json())
</script>

<style>
  .my-selected{
    font-size: 48px;
    color:rgb(0, 20, 176);
  }

  .my-menu-selected{
    font-size: 14px;
    font-weight: bold;
    color:#B1DC4C !important;
  }
</style>
  