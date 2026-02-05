import { createStore } from 'vuex'
import actions from "./actions.js"
import mutations from "./mutations.js"
import state from "./state.js"
export default createStore({
    state,
    actions, //异步方法
    mutations,//同步方法
})