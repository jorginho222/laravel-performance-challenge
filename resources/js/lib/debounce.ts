export function debounce<Args extends unknown[]>(callback: (...args: Args) => void, wait: number): (...args: Args) => void {
    let timer: ReturnType<typeof setTimeout> | undefined;

    return (...args: Args) => {
        clearTimeout(timer);
        timer = setTimeout(() => callback(...args), wait);
    };
}
