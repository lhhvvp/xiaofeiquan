const  mutations = {
    SET_TOKEN: (state, token) => {
        state.token = token
    },
    USER_INFO:(state, options)=>{
        state.UserInfo = options;
    }
};
export default  mutations