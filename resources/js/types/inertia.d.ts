import type { Auth, Toast } from '@/types';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            auth: Auth;
        };
        flashDataType: {
            toast?: Toast;
        };
    }
}
