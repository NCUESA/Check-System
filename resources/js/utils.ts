import { useToast } from "vue-toast-notification";
import 'vue-toast-notification/dist/theme-bootstrap.css';


const toast = useToast();

export const toastSuccessMessage = (message: any) => {
    toast.success(message);
}

export const toastSuccessMessages = (messages: any) => {
    const msgs = Object.entries(messages);
    msgs.forEach((v) => {
        toast.success(`${v[1]}`);
    });
}

export const toastInfoMessage = (message: any) => {
    toast.info(message);
}

export const toastInfoMessages = (messages: any) => {
    const msgs = Object.entries(messages);
    msgs.forEach((v) => {
        toast.success(`${v[1]}`);
    });
}

export const toastPrimaryMessage = (message: any) => {
    toast.default(message);
}

export const toastErrorMessage = (message: any) => {
    toast.error(message);
}

export const toastErrors = (errors: any) => {
    const errs = Object.entries(errors);
    errs.forEach((v) => {
        toast.error(`${v[1]}`);
    });
}