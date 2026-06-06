export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md bg-white">
                <img src="/images/hfc-logo.png" alt="Home Finders Coastal" className="size-full object-contain p-0.5" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-none font-semibold">Home Finders Coastal</span>
            </div>
        </>
    );
}
