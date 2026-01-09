import * as adminContent from './routes/admin/content';
import * as adminSchema from './routes/admin/schema';
import * as adminSettings from './routes/admin/settings';
import * as adminSettingsTokens from './routes/admin/settings/tokens';
import * as adminSettingsProfile from './routes/admin/settings/profile';
import * as adminSettingsPassword from './routes/admin/settings/password';

export function route(name: string, params?: any): string {
    const routeMap: Record<string, any> = {
        'admin.schema.create': adminSchema.create,
        'admin.schema.store': adminSchema.store,
        'admin.schema.index': adminSchema.index,
        'admin.schema.edit': adminSchema.edit,
        'admin.schema.update': adminSchema.update,
        'admin.content.index': adminContent.index,
        'admin.content.create': adminContent.create,
        'admin.content.store': adminContent.store,
        'admin.content.edit': adminContent.edit,
        'admin.content.update': adminContent.update,
        'admin.content.destroy': adminContent.destroy,
        'admin.settings.index': adminSettings.index,
        'admin.settings.profile.update': adminSettingsProfile.update,
        'admin.settings.password.update': adminSettingsPassword.update,
        'admin.settings.tokens.store': adminSettingsTokens.store,
        'admin.settings.tokens.destroy': adminSettingsTokens.destroy,
    };

    const routeFn = routeMap[name];

    if (!routeFn) {
        console.error(`Route "${name}" not found`);
        return '#';
    }

    const result = routeFn(params);
    return result.url || result;
}
