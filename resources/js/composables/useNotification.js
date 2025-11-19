import { useToastr } from '@/Components/toster'

export function useNotification() {
  const toastr = useToastr()

  return {
    notify: (type, message) => {
      if (type === 'success') {
        toastr.success(message)
      } else if (type === 'error') {
        toastr.error(message)
      } else if (type === 'warning') {
        toastr.warning(message)
      } else if (type === 'info') {
        toastr.info(message)
      }
    },
  }
}
