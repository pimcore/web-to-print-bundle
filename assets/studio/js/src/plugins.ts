import { type IAbstractPlugin } from '@pimcore/studio-ui-bundle'
import { WebToPrintEditorModule } from './modules/web-to-print'

if (module.hot !== undefined) {
  module.hot.accept()
}

export const WebToPrintPlugin: IAbstractPlugin = {
  name: 'pimcore-webtoprint-plugin',

  // Register and overwrite services here
  onInit: ({ container }): void => {

  },

  // register modules here
  onStartup: ({ moduleSystem }): void => {
    moduleSystem.registerModule(WebToPrintEditorModule)
    console.log('Hello from web to print.')
  }
}
