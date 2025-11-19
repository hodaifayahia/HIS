<template>
  <div>
    <Dialog :visible="dialogVisible" @close="handleClose">
      <!-- Dialog content -->
    </Dialog>
  </div>
</template>

<script>
import { computed } from 'vue'

export default {
  props: {
    modelValue: {
      type: Boolean,
      required: true
    }
  },
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    const dialogVisible = computed({
      get: () => {
        // return the actual prop value so the Dialog opens when parent sets it
        return props.modelValue
      },
      set: (value) => {
        // emit the real value back to the parent so it can react to close/open actions
        emit('update:modelValue', value)
      }
    })

    const handleClose = () => {
      // This method will be called when the dialog is requested to be closed
      dialogVisible.value = false
    }

    return {
      dialogVisible,
      handleClose
    }
  }
}
</script>

<style scoped>
/* Add any necessary styles here */
</style>