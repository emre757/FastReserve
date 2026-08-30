import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/react';
import {
    CalendarIcon,
    ChevronDownIcon,
    CurrencyDollarIcon,
    PencilIcon,
    ArrowRightStartOnRectangleIcon,
    NoSymbolIcon,
    TrashIcon,
} from '@heroicons/react/20/solid';
import { router } from '@inertiajs/react';
import { Clock, LoaderCircleIcon } from 'lucide-react';
import { useState } from 'react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogMedia,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { destroy } from '@/routes/offerings';

type Props = {
    id: number;
    name: string;
    timezone: string;
    starts_at: string;
    ends_at: string | null;
    booking_deadline_at: string | null;
    cancellation_deadline_at: string | null;
    can_edit: boolean;
    can_delete: boolean;
};

export default function OfferingHeader({
    id,
    name,
    timezone,
    starts_at,
    ends_at,
    booking_deadline_at,
    cancellation_deadline_at,
    can_edit,
    can_delete,
}: Props) {
    const [archiveDialogOpen, setArchiveDialogOpen] = useState(false);
    const [processing, setProcessing] = useState<boolean>(false);

    return (
        <>
            <div className="lg:flex lg:items-center lg:justify-between">
                <div className="min-w-0 flex-1">
                    <h1 className="text-2xl/7 font-bold text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight dark:text-white">
                        {name}
                    </h1>
                    <div className="mt-1 flex flex-col sm:mt-0 sm:flex-row sm:flex-wrap sm:space-x-6">
                        <div className="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-400">
                            <Clock
                                aria-hidden="true"
                                className="mr-1.5 size-5 shrink-0 text-gray-400 dark:text-gray-500"
                            />
                            {timezone}
                        </div>
                        <div className="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-400">
                            <ArrowRightStartOnRectangleIcon
                                aria-hidden="true"
                                className="mr-1.5 size-5 shrink-0 text-gray-400 dark:text-gray-500"
                            />
                            Starts on {starts_at}
                        </div>
                        <div className="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-400">
                            <NoSymbolIcon
                                aria-hidden="true"
                                className="mr-1.5 size-5 shrink-0 text-gray-400 dark:text-gray-500"
                            />
                            {booking_deadline_at &&
                                booking_deadline_at !== 'No deadline' &&
                                'Booking closes on'}{' '}
                            {booking_deadline_at}
                        </div>
                        <div className="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-400">
                            <CurrencyDollarIcon
                                aria-hidden="true"
                                className="mr-1.5 size-5 shrink-0 text-gray-400 dark:text-gray-500"
                            />
                            {cancellation_deadline_at &&
                                cancellation_deadline_at !== 'No deadline' &&
                                'Cancellation deadline: '}{' '}
                            {cancellation_deadline_at}
                        </div>
                        <div className="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-400">
                            <CalendarIcon
                                aria-hidden="true"
                                className="mr-1.5 size-5 shrink-0 text-gray-400 dark:text-gray-500"
                            />
                            {ends_at && ends_at !== 'No deadline'
                                ? `Closing on ${ends_at}`
                                : 'No closing date'}
                        </div>
                    </div>
                </div>

                {/*actions*/}
                {(can_edit || can_delete) && (
                    <div className="mt-5 flex lg:mt-0 lg:ml-4">
                        {can_delete && (
                            <span className="hidden sm:block">
                                <button
                                    type="button"
                                    className="inline-flex items-center rounded-md bg-red-500 px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs inset-ring inset-ring-gray-300 hover:bg-red-400 dark:text-white dark:shadow-none dark:inset-ring-white/5"
                                    disabled={processing}
                                    onClick={() => setArchiveDialogOpen(true)}
                                >
                                    <TrashIcon
                                        aria-hidden="true"
                                        className="mr-1.5 -ml-0.5 size-5 text-gray-400 dark:text-white"
                                    />
                                    {processing ? 'Archiving..' : 'Archive'}
                                </button>
                            </span>
                        )}

                        {can_edit && (
                            <span className="sm:ml-3">
                                <button
                                    type="button"
                                    className="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:bg-indigo-500 dark:shadow-none dark:hover:bg-indigo-400 dark:focus-visible:outline-indigo-500"
                                >
                                    <PencilIcon
                                        aria-hidden="true"
                                        className="mr-1.5 -ml-0.5 size-5"
                                    />
                                    Edit
                                </button>
                            </span>
                        )}

                        {/* Dropdown for small devices */}
                        {can_delete && (
                            <Menu as="div" className="relative ml-3 sm:hidden">
                                <MenuButton className="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs inset-ring inset-ring-gray-300 hover:bg-gray-50 dark:bg-white/10 dark:text-white dark:shadow-none dark:inset-ring-white/5 dark:hover:bg-white/20">
                                    More
                                    <ChevronDownIcon
                                        aria-hidden="true"
                                        className="-mr-1 ml-1.5 size-5 text-gray-400"
                                    />
                                </MenuButton>

                                <MenuItems
                                    transition
                                    className="absolute left-0 z-10 mt-2 -mr-1 w-24 origin-top-right rounded-md bg-white py-1 shadow-lg outline outline-black/5 transition data-closed:scale-95 data-closed:transform data-closed:opacity-0 data-enter:duration-200 data-enter:ease-out data-leave:duration-75 data-leave:ease-in dark:shadow-none dark:-outline-offset-1 dark:outline-white/10"
                                >
                                    <MenuItem>
                                        <button
                                            onClick={() =>
                                                setArchiveDialogOpen(true)
                                            }
                                            className="block px-4 py-2 text-sm data-focus:bg-gray-100 data-focus:outline-hidden dark:text-gray-300 dark:data-focus:bg-white/5"
                                        >
                                            Archive
                                        </button>
                                    </MenuItem>
                                </MenuItems>
                            </Menu>
                        )}
                    </div>
                )}
            </div>
            <ArchiveDialog
                name={name}
                open={archiveDialogOpen}
                processing={processing}
                onOpenChange={setArchiveDialogOpen}
                onConfirm={() => {
                    router.delete(destroy(id), {
                        onStart: () => setProcessing(true),
                        onSuccess: () => setArchiveDialogOpen(false),
                        onFinish: () => setProcessing(false),
                    });
                }}
            />
        </>
    );
}

type DialogProps = {
    name: string;
    open: boolean;
    processing: boolean;
    onOpenChange: (open: boolean) => void;
    onConfirm: () => void;
};

function ArchiveDialog(props: DialogProps) {
    return (
        <AlertDialog open={props.open} onOpenChange={props.onOpenChange}>
            <AlertDialogContent size="sm">
                <AlertDialogHeader>
                    <AlertDialogMedia className="bg-destructive/10 text-destructive dark:bg-destructive/20 dark:text-destructive">
                        <TrashIcon />
                    </AlertDialogMedia>
                    <AlertDialogTitle>Archive offering?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This will archive{' '}
                        <span className={'font-bold'}> {props.name} </span>
                        <br />
                        Offer can be restored in the admin panel after
                        archiving.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel
                        disabled={props.processing}
                        variant="outline"
                    >
                        Cancel
                    </AlertDialogCancel>
                    <AlertDialogAction
                        variant="destructive"
                        disabled={props.processing}
                        onClick={(e) => {
                            e.preventDefault();
                            props.onConfirm();
                        }}
                    >
                        {props.processing ? (
                            <>
                                <LoaderCircleIcon className={'animate-spin'} />
                                <span>Archiving..</span>
                            </>
                        ) : (
                            'Archive'
                        )}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
