import { useAsyncState } from '@vueuse/core'
import { defineStore } from 'pinia'
import { mande } from 'mande'
import params from '../../config/parameters'
import { defaultOptions } from '../utils/api'

const account = mande(`${params.api.host}/user`, defaultOptions)

export interface Account {
  id: string;
  name: string;
  picture: string;
}

export const useAccountStore = defineStore({
  id: 'account',
  state: () => ({
    account: useAsyncState<Account | null>(
      account.get(''),
      null,
    ),
  }),
})
