import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
            {[0, 60, 120, 180, 240, 300].map((angle) => (
                <g key={angle} transform={`rotate(${angle} 20 20)`}>
                    {/* Main arm from tip to center */}
                    <rect x="18.25" y="3" width="3.5" height="17" rx="1.75" />
                    {/* Outer branches (pivot at y=8, 12 units from center) */}
                    <rect x="18.5" y="4" width="3" height="4" rx="1.5" transform="rotate(60 20 8)" />
                    <rect x="18.5" y="4" width="3" height="4" rx="1.5" transform="rotate(-60 20 8)" />
                    {/* Inner branches (pivot at y=13, 7 units from center) */}
                    <rect x="18.5" y="8" width="3" height="5" rx="1.5" transform="rotate(60 20 13)" />
                    <rect x="18.5" y="8" width="3" height="5" rx="1.5" transform="rotate(-60 20 13)" />
                    {/* Tip bead */}
                    <circle cx="20" cy="3" r="2" />
                </g>
            ))}
            {/* Center hub */}
            <circle cx="20" cy="20" r="4" />
        </svg>
    );
}
