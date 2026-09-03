import { Search, X } from "lucide-react"
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from "@/components/ui/input-group"

import { Button } from "@/components/ui/button"
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { useEffect, useRef, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { ScrollArea } from '@/components/ui/scroll-area';

export type FilterOption = {
    label: string;
    value: string;
    disabled?: boolean;
};

export type FilterProps = {
    options: FilterOption[];
    selected: string[];
    onChange: (selected: string[]) => void;
};

type Props = {
    initialSearch: string;
    resultCount: number;
    onSearch: (search: string) => void;
    filterProps: FilterProps;
}


export default function SearchBar({ initialSearch, resultCount, onSearch, filterProps }: Props)
{
    const searchRef = useRef<string>(initialSearch);
    const [search, setSearch] = useState<string>(initialSearch);

    useEffect(() => {
        if (searchRef.current === search) {
            return;
        }

        const timeout = setTimeout(() => {
            searchRef.current = search;
            onSearch(search);
        }, 300);

        return () => clearTimeout(timeout);
    }, [search]);

    return (
        <div className="mx-auto flex w-full max-w-xl flex-wrap items-center gap-2">
            <InputGroup className="min-w-0 flex-1">
                <InputGroupInput type={'search'} placeholder="Search..." value={search} onChange={(e) => setSearch(e.target.value)} />
                <InputGroupAddon>
                    <Search />
                </InputGroupAddon>
                <InputGroupAddon align="inline-end">{resultCount} result{resultCount !== 1 && 's'}</InputGroupAddon>
            </InputGroup>
            <FilterDropDown {...filterProps} />
        </div>
    )
}

function FilterDropDown({ options, selected, onChange }: FilterProps)
{
    return (
        <>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="outline">Filters</Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent className="w-40">
                    <DropdownMenuGroup>
                        <DropdownMenuLabel>Offering Filters</DropdownMenuLabel>
                        {options.map((option) => (
                            <DropdownMenuCheckboxItem
                                key={option.value}
                                checked={selected.includes(option.value)}
                                onCheckedChange={(checked) => {
                                    const updatedFilters =
                                        checked
                                            ? [...selected, option.value] : selected.filter((value) => value !== option.value)
                                    onChange(updatedFilters);
                                }}
                            >
                                {option.label}
                            </DropdownMenuCheckboxItem>
                        ))}
                    </DropdownMenuGroup>
                </DropdownMenuContent>
            </DropdownMenu>
            {selected.length > 0 && (
                <ScrollArea className="h-16 w-full rounded-lg border bg-muted/30 shadow-inner">
                    <div className="flex flex-wrap gap-1.5 p-2 pr-4">
                        {options
                            .filter((option) => selected.includes(option.value))
                            .map((option) => (
                                <Badge
                                    key={option.value}
                                    variant="secondary"
                                    className="gap-1 rounded-full pr-1"
                                >
                                    {option.label}
                                    <button
                                        type="button"
                                        className="rounded-full p-0.5 transition-colors hover:bg-foreground/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                        onClick={() =>
                                            onChange(
                                                selected.filter(
                                                    (value) => value !== option.value,
                                                ),
                                            )
                                        }
                                    >
                                        <X className="size-3" />
                                    </button>
                                </Badge>
                            ))}
                    </div>
                </ScrollArea>
            )}
        </>
    )
}
