# Component contract

No screen may introduce a one-off control where a shared component can be extended. Screens compose components; they do not redraw them.

| ID | Purpose | Owner |
|---|---|---|
| CMP-Button, CMP-IconButton, CMP-Link | actions/navigation | Shared |
| CMP-Input, CMP-Select, CMP-MultiSelect, CMP-Combobox, CMP-Checkbox, CMP-Radio, CMP-Switch | fields | Shared |
| CMP-Tabs, CMP-FilterChip, CMP-Toolbar, CMP-FilterToolbar, CMP-Pagination | navigation/filtering | Shared |
| CMP-Notice, CMP-Toast, CMP-EmptyState, CMP-Skeleton, CMP-Modal, CMP-Drawer, CMP-SidePanel, CMP-BottomSheet | feedback/overlays | Shared |
| CMP-LocationCard, CMP-LocationListRow, CMP-LocationDetail, CMP-MapCanvas, CMP-MapMarker, CMP-MapCluster, CMP-MapPopup | locator UI | FO |
| CMP-PageHeader, CMP-PluginNavigation, CMP-AdminTable, CMP-EditorSection, CMP-StickySaveBar, CMP-LivePreview, CMP-ProviderCard, CMP-LicenseStatus | admin UI | BO |
