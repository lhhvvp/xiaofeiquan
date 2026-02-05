import {getToken ,getUserInfo} from '@/utils/auth'

const stateAll = () => {
    return {
        token: getToken(), //token
        UserInfo:getUserInfo(),
    }
};
export default stateAll;


