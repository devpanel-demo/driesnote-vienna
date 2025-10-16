import {
  createBrowserRouter,
  Navigate,
  Outlet,
  RouterProvider,
} from 'react-router-dom';
import { Flex } from '@radix-ui/themes';

import App from '@/app/App';
import ComponentInstanceForm from '@/components/ComponentInstanceForm';
import { RouteErrorBoundary } from '@/components/error/ErrorBoundary';
import ErrorCard from '@/components/error/ErrorCard';
import ExtensionDialog from '@/components/extensions/ExtensionDialog';
import PermissionCheck from '@/components/PermissionCheck';
import SideMenu from '@/components/sideMenu/SideMenu';
import PrimaryPanel from '@/components/sidePanel/PrimaryPanel';
import CodeComponentDialogs from '@/features/code-editor/dialogs/CodeComponentDialogs';
import MosaicContainer from '@/features/code-editor/MosaicContainer';
import Editor from '@/features/editor/Editor';
import TemplateRoot from '@/features/editor/TemplateRoot';
import PagePreview from '@/features/pagePreview/PagePreview';
import PatternDialogs from '@/features/pattern/PatternDialogs';
import SegmentDashboard from '@/features/personalization/SegmentDashboard';
import SegmentPanel from '@/features/personalization/SegmentPanel';
import { EditorFrameContext } from '@/features/ui/uiSlice';
import Welcome from '@/features/welcome/Welcome';

import type React from 'react';

interface AppRoutesInterface {
  basePath: string;
}

const UiShell = ({ children }: { children: React.ReactNode }) => (
  <>
    <SideMenu />
    <PrimaryPanel />
    {children}
    <Dialogs />
  </>
);

const CodeEditorUi = (
  <PermissionCheck
    hasPermission="codeComponents"
    denied={
      <Flex align="center" justify="center" height="100vh" width="100%">
        <ErrorCard
          title="You do not have permission to access the code editor."
          error="Please contact your site administrator if you believe this is an error."
        />
      </Flex>
    }
  >
    <UiShell>
      <MosaicContainer />
    </UiShell>
  </PermissionCheck>
);

const Dialogs = () => (
  <div style={{ position: 'absolute' }}>
    <PatternDialogs />
    <CodeComponentDialogs />
    <ExtensionDialog />
  </div>
);

const AppRoutes: React.FC<AppRoutesInterface> = ({ basePath }) => {
  const router = createBrowserRouter(
    [
      {
        path: '',
        element: <App />,
        errorElement: <RouteErrorBoundary />,
        children: [
          {
            index: true, // base path
            element:
              basePath === '/canvas' ? (
                <UiShell>
                  <Welcome />
                </UiShell>
              ) : (
                <Navigate to="/editor" replace />
              ),
          },
          {
            path: '/editor/',
            element: (
              <UiShell>
                <Welcome />
              </UiShell>
            ),
          },
          {
            path: '/editor/:entityType/:entityId',
            element: (
              <UiShell>
                <Editor context={EditorFrameContext.ENTITY} />
              </UiShell>
            ),
            children: [
              {
                path: '/editor/:entityType/:entityId/region/:regionId/component/:componentId',
                element: <ComponentInstanceForm />,
              },
              {
                path: '/editor/:entityType/:entityId/region/:regionId',
                element: <ComponentInstanceForm />,
              },
              {
                path: '/editor/:entityType/:entityId/component/:componentId',
                element: <ComponentInstanceForm />,
              },
            ],
          },
          {
            path: '/template/:entityType/:bundle/:viewMode',
            element: (
              <UiShell>
                <TemplateRoot />
              </UiShell>
            ),
          },
          {
            path: '/template/:entityType/:bundle/:viewMode/:previewEntityId',
            element: (
              <UiShell>
                <Editor context={EditorFrameContext.TEMPLATE} />
              </UiShell>
            ),
            children: [
              {
                path: '/template/:entityType/:bundle/:viewMode/:previewEntityId/region/:regionId/component/:componentId',
                element: <ComponentInstanceForm />,
              },
              {
                path: '/template/:entityType/:bundle/:viewMode/:previewEntityId/region/:regionId',
                element: <ComponentInstanceForm />,
              },
              {
                path: '/template/:entityType/:bundle/:viewMode/:previewEntityId/component/:componentId',
                element: <ComponentInstanceForm />,
              },
            ],
          },
          {
            path: '/preview/:entityType/:entityId/',
            element: <PagePreview />,
          },
          {
            path: 'preview/:entityType/:entityId/:width',
            element: <PagePreview />,
          },
          {
            // Opens the code editor for an item under 'Code'.
            path: '/code-editor/code/:codeComponentId',
            element: CodeEditorUi,
          },
          {
            // Opens the code editor for an item under 'Components'.
            path: '/code-editor/component/:codeComponentId',
            element: CodeEditorUi,
          },
          {
            // Personalization
            path: '/segments/',
            element: (
              <SegmentPanel>
                <Outlet />
              </SegmentPanel>
            ),
            children: [
              {
                path: '/segments/',
                element: <SegmentDashboard />,
              },
              {
                path: '/segments/:segmentId',
                element: <h1>Segment Details</h1>,
              },
            ],
          },
        ],
      },
    ],
    {
      basename: `${basePath}`,
      future: {
        v7_fetcherPersist: true,
        v7_normalizeFormMethod: true,
        v7_partialHydration: true,
        v7_relativeSplatPath: true,
        v7_skipActionErrorRevalidation: true,
      },
    },
  );

  return <RouterProvider router={router} />;
};

export default AppRoutes;
