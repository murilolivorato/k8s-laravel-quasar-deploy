<template>
  <q-layout view="lHh Lpr lFf">
    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup>
// No additional setup needed for this layout
</script>

<style scoped>
.q-page-container {
  padding-top: 0;
  min-height: 100vh;
  background: linear-gradient(135deg, #1976D2 0%, #2196F3 100%);
}
</style> 